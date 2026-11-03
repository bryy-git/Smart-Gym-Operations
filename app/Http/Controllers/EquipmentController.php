<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Services\EquipmentAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EquipmentController extends Controller
{
    public function index(Request $request, EquipmentAlertService $equipmentAlertService): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['Available', 'Under Maintenance', 'Unavailable'])],
        ]);
        $routePrefix = $this->routePrefix($request);

        $equipment = $equipmentAlertService->withUsageSinceMaintenance()
            ->with(['maintenanceRecords' => function (HasMany $query): void {
                $query->whereIn('maintenance_status', ['Reported', 'In Progress'])
                    ->orderByDesc('reported_at');
            }])
            ->when(
                ! empty($validated['search']),
                fn (Builder $query): Builder => $query->whereRaw(
                    'LOWER(equipment.equipment_name) LIKE ?',
                    ['%'.mb_strtolower($validated['search']).'%'],
                ),
            )
            ->when(
                ! empty($validated['status']),
                fn (Builder $query): Builder => $query->where('equipment.equipment_status', $validated['status']),
            )
            ->orderBy('equipment.equipment_name')
            ->orderBy('equipment.equipment_id')
            ->paginate(15)
            ->appends($request->query());
        $alertCount = $equipmentAlertService->alerts()->count();

        return view('equipment.index', [
            'equipment' => $equipment,
            'routePrefix' => $routePrefix,
            'search' => $validated['search'] ?? '',
            'status' => $validated['status'] ?? '',
            'alertCount' => $alertCount,
        ]);
    }

    public function create(Request $request): View
    {
        return view('equipment.form', [
            'equipment' => null,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->equipmentRules());
        $equipment = Equipment::query()->create($validated);

        return redirect()
            ->route($this->routePrefix($request).'equipment.show', $equipment)
            ->with('success', 'Equipment added successfully.');
    }

    public function show(
        Request $request,
        Equipment $equipment,
        EquipmentAlertService $equipmentAlertService,
    ): View {
        $equipment->load([
            'maintenanceRecords' => function (HasMany $query): void {
                $query->whereIn('maintenance_status', ['Reported', 'In Progress'])
                    ->with(['reporter', 'updater'])
                    ->orderByDesc('reported_at')
                    ->limit(5);
            },
        ]);
        $equipment->load([
            'usageRecords' => function (HasMany $query): void {
                $query->with('recorder')
                    ->orderByDesc('usage_date')
                    ->orderByDesc('usage_id')
                    ->limit(5);
            },
        ]);
        $usage = $equipmentAlertService->withUsageSinceMaintenance()
            ->where('equipment.equipment_id', $equipment->equipment_id)
            ->firstOrFail();

        return view('equipment.show', [
            'equipment' => $equipment,
            'usageSinceMaintenance' => $usage->usage_since_maintenance,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function edit(Request $request, Equipment $equipment): View
    {
        return view('equipment.form', [
            'equipment' => $equipment,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function update(Request $request, Equipment $equipment): RedirectResponse
    {
        $validated = $request->validate($this->equipmentRules());
        DB::transaction(function () use ($equipment, $validated): void {
            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->equipment_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureStatusCanBeSet($lockedEquipment, $validated['equipment_status']);
            $lockedEquipment->update($validated);
        });

        return redirect()
            ->route($this->routePrefix($request).'equipment.show', $equipment)
            ->with('success', 'Equipment details updated successfully.');
    }

    public function maintenanceHistory(Request $request, Equipment $equipment): View
    {
        $records = $equipment->maintenanceRecords()
            ->with(['reporter', 'updater'])
            ->orderByDesc('reported_at')
            ->orderByDesc('maintenance_id')
            ->paginate(15);

        return view('equipment.maintenance-history', [
            'equipment' => $equipment,
            'records' => $records,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function reportIssue(Request $request, Equipment $equipment): RedirectResponse
    {
        $validated = $request->validate([
            'issue_description' => ['required', 'string', 'max:10000'],
        ]);

        DB::transaction(function () use ($equipment, $request, $validated): void {
            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->equipment_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedEquipment->maintenanceRecords()->create([
                'reported_by' => $request->user()->user_id,
                'issue_description' => $validated['issue_description'],
                'maintenance_status' => 'Reported',
                'reported_at' => now(),
                'updated_by' => $request->user()->user_id,
                'updated_at' => now(),
            ]);
            $lockedEquipment->equipment_status = 'Under Maintenance';
            $lockedEquipment->save();
        });

        return redirect()
            ->route($this->routePrefix($request).'equipment.show', $equipment)
            ->with('success', 'Equipment issue reported and added to maintenance history.');
    }

    public function updateMaintenance(
        Request $request,
        Equipment $equipment,
        MaintenanceRecord $maintenanceRecord,
    ): RedirectResponse {
        abort_unless((int) $maintenanceRecord->equipment_id === (int) $equipment->equipment_id, 404);
        $validated = $request->validate([
            'maintenance_status' => ['required', Rule::in(['Reported', 'In Progress', 'Resolved'])],
            'action_details' => ['nullable', 'string', 'max:10000'],
        ]);

        DB::transaction(function () use ($equipment, $maintenanceRecord, $request, $validated): void {
            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->equipment_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedRecord = MaintenanceRecord::query()
                ->whereKey($maintenanceRecord->maintenance_id)
                ->where('equipment_id', $lockedEquipment->equipment_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRecord->maintenance_status = $validated['maintenance_status'];
            $lockedRecord->action_details = $validated['action_details'] ?? null;
            $lockedRecord->resolved_at = $validated['maintenance_status'] === 'Resolved' ? now() : null;
            $lockedRecord->updated_by = $request->user()->user_id;
            $lockedRecord->updated_at = now();
            $lockedRecord->save();

            if ($lockedRecord->maintenance_status !== 'Resolved') {
                $lockedEquipment->equipment_status = 'Under Maintenance';
            } elseif (
                $lockedEquipment->equipment_status === 'Under Maintenance'
                && ! $lockedEquipment->maintenanceRecords()
                    ->whereIn('maintenance_status', ['Reported', 'In Progress'])
                    ->exists()
            ) {
                $lockedEquipment->equipment_status = 'Available';
            }

            $lockedEquipment->save();
        });

        return redirect()
            ->route($this->routePrefix($request).'equipment.maintenance-history', $equipment)
            ->with('success', 'Maintenance status updated successfully.');
    }

    public function usageHistory(Request $request, Equipment $equipment): View
    {
        $records = $equipment->usageRecords()
            ->with('recorder')
            ->orderByDesc('usage_date')
            ->orderByDesc('usage_id')
            ->paginate(15);

        return view('equipment.usage-history', [
            'equipment' => $equipment,
            'records' => $records,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function recordUsage(Request $request, Equipment $equipment): RedirectResponse
    {
        $validated = $request->validate([
            'usage_count' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'usage_date' => ['required', 'date', 'before_or_equal:'.today()->toDateString()],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
        $equipment->usageRecords()->create([
            'recorded_by' => $request->user()->user_id,
            'usage_count' => $validated['usage_count'],
            'usage_date' => $validated['usage_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route($this->routePrefix($request).'equipment.usage-history', $equipment)
            ->with('success', 'Equipment usage recorded successfully.');
    }

    private function equipmentRules(): array
    {
        return [
            'equipment_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:10000'],
            'equipment_status' => ['required', Rule::in(['Available', 'Under Maintenance', 'Unavailable'])],
            'usage_threshold' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
        ];
    }

    private function ensureStatusCanBeSet(Equipment $equipment, string $status): void
    {
        if (
            $status === 'Available'
            && $equipment->maintenanceRecords()
                ->whereIn('maintenance_status', ['Reported', 'In Progress'])
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'equipment_status' => 'Resolve all open maintenance records before marking equipment Available.',
            ]);
        }
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'staff.')
            ? 'staff.'
            : 'admin.';
    }
}
