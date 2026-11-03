<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\SystemSetting;
use App\Services\AttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $attendanceService): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(['open', 'closed'])],
            'source' => ['nullable', Rule::in(['Staff-assisted', 'Member self-service'])],
        ]);
        $routePrefix = $this->routePrefix($request);

        $attendances = Attendance::query()
            ->with(['member.user'])
            ->when(
                ! empty($validated['search']),
                function (Builder $query) use ($validated): void {
                    $pattern = '%'.mb_strtolower($validated['search']).'%';
                    $query->whereHas('member', function (Builder $members) use ($pattern): void {
                        $members
                            ->whereRaw('LOWER(first_name) LIKE ?', [$pattern])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$pattern])
                            ->orWhereHas('user', fn (Builder $users): Builder => $users->whereRaw('LOWER(email) LIKE ?', [$pattern]));
                    });
                },
            )
            ->when(
                ! empty($validated['date_from']),
                fn (Builder $query): Builder => $query->whereDate('check_in_at', '>=', $validated['date_from']),
            )
            ->when(
                ! empty($validated['date_to']),
                fn (Builder $query): Builder => $query->whereDate('check_in_at', '<=', $validated['date_to']),
            )
            ->when(
                ($validated['status'] ?? '') === 'open',
                fn (Builder $query): Builder => $query->whereNull('check_out_at'),
            )
            ->when(
                ($validated['status'] ?? '') === 'closed',
                fn (Builder $query): Builder => $query->whereNotNull('check_out_at'),
            )
            ->when(
                ! empty($validated['source']),
                fn (Builder $query): Builder => $query->where('attendance_source', $validated['source']),
            )
            ->orderByDesc('check_in_at')
            ->orderByDesc('attendance_id')
            ->paginate(15)
            ->appends($request->query());
        $maximumCapacity = SystemSetting::query()
            ->where('setting_key', AttendanceService::MAXIMUM_CAPACITY_SETTING)
            ->value('setting_value');

        return view('attendance.index', [
            'routePrefix' => $routePrefix,
            'attendances' => $attendances,
            'filters' => $validated,
            'occupancy' => $attendanceService->occupancy(),
            'maximumCapacity' => $maximumCapacity,
        ]);
    }

    public function create(): View
    {
        $members = Member::query()
            ->with('user')
            ->where('member_status', 'Active')
            ->whereHas('user', fn (Builder $query): Builder => $query->where('account_status', 'Active'))
            ->whereHas('memberships', function (Builder $query): void {
                $query->where('membership_status', 'Active')
                    ->whereDate('start_date', '<=', today()->toDateString())
                    ->whereDate('end_date', '>=', today()->toDateString());
            })
            ->whereDoesntHave('attendances', fn (Builder $query): Builder => $query->whereNull('check_out_at'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('attendance.staff-check-in', [
            'members' => $members,
            'routePrefix' => $this->routePrefix(request()),
        ]);
    }

    public function staffCheckIn(
        Request $request,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'member_id')],
        ]);
        $member = Member::query()->findOrFail($validated['member_id']);
        $attendanceService->checkIn($member, 'Staff-assisted');

        return redirect()
            ->route($this->routePrefix($request).'attendance.index')
            ->with('success', 'Member checked in successfully.');
    }

    public function staffCheckOut(
        Request $request,
        Attendance $attendance,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $attendanceService->checkOut($attendance);

        return redirect()
            ->route($this->routePrefix($request).'attendance.index')
            ->with('success', 'Member checked out successfully.');
    }

    public function memberIndex(Request $request): View
    {
        $member = Member::query()
            ->where('user_id', $request->user()->user_id)
            ->with('user')
            ->firstOrFail();
        $attendances = $member->attendances()
            ->orderByDesc('check_in_at')
            ->orderByDesc('attendance_id')
            ->paginate(15);
        $openAttendance = $member->attendances()
            ->whereNull('check_out_at')
            ->orderByDesc('check_in_at')
            ->first();
        $hasValidMembership = $member->member_status === 'Active'
            && $member->user?->account_status === 'Active'
            && $member->memberships()
                ->where('membership_status', 'Active')
                ->whereDate('start_date', '<=', today()->toDateString())
                ->whereDate('end_date', '>=', today()->toDateString())
                ->exists();

        return view('attendance.member-index', [
            'attendances' => $attendances,
            'openAttendance' => $openAttendance,
            'hasValidMembership' => $hasValidMembership,
        ]);
    }

    public function memberCheckIn(
        Request $request,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $member = Member::query()
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
        $attendanceService->checkIn($member, 'Member self-service');

        return redirect()
            ->route('member.attendance.index')
            ->with('success', 'You are checked in.');
    }

    public function memberCheckOut(
        Request $request,
        Attendance $attendance,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $memberId = Member::query()
            ->where('user_id', $request->user()->user_id)
            ->value('member_id');
        abort_if($memberId === null || (int) $attendance->member_id !== (int) $memberId, 404);
        $attendanceService->checkOut($attendance);

        return redirect()
            ->route('member.attendance.index')
            ->with('success', 'You are checked out.');
    }

    public function occupancy(AttendanceService $attendanceService): JsonResponse
    {
        return response()->json($attendanceService->occupancy());
    }

    public function updateCapacity(
        Request $request,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $validated = $request->validate([
            'maximum_capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        $attendanceService->setMaximumCapacity(
            (int) $validated['maximum_capacity'],
            $request->user(),
        );

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Maximum capacity updated.');
    }

    public function edit(Attendance $attendance): View
    {
        $attendance->load(['member.user', 'corrections.corrector']);

        return view('attendance.edit', [
            'attendance' => $attendance,
            'routePrefix' => $this->routePrefix(request()),
        ]);
    }

    public function update(
        Request $request,
        Attendance $attendance,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $validated = $request->validate([
            'check_in_at' => ['required', 'date', 'before_or_equal:now'],
            'check_out_at' => ['nullable', 'date', 'after:check_in_at', 'before_or_equal:now'],
            'correction_reason' => ['required', 'string', 'max:5000'],
        ]);
        $attendanceService->correct(
            $attendance,
            $request->user(),
            Carbon::parse($validated['check_in_at']),
            isset($validated['check_out_at']) ? Carbon::parse($validated['check_out_at']) : null,
            $validated['correction_reason'],
        );

        return redirect()
            ->route($this->routePrefix($request).'attendance.index')
            ->with('success', 'Attendance was corrected and the original values were recorded.');
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'staff.')
            ? 'staff.'
            : 'admin.';
    }
}
