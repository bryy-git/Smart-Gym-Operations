<?php

namespace App\Http\Controllers;

use App\Models\GymClosure;
use App\Models\OperatingSchedule;
use App\Services\ClosureAnnouncementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperatingScheduleController extends Controller
{
    private const DAY_NAMES = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public function adminIndex(): View
    {
        return view('operating-schedules.admin-index', [
            'schedules' => OperatingSchedule::query()
                ->orderBy('day_of_week')
                ->orderBy('opening_time')
                ->get()
                ->groupBy('day_of_week'),
            'dayNames' => self::DAY_NAMES,
        ]);
    }

    public function publicIndex(
        Request $request,
        ClosureAnnouncementService $closureAnnouncementService,
    ): View {
        return view('operating-schedules.public-index', [
            'schedules' => OperatingSchedule::query()
                ->where('is_open', true)
                ->orderBy('day_of_week')
                ->orderBy('opening_time')
                ->get()
                ->groupBy('day_of_week'),
            'dayNames' => self::DAY_NAMES,
            'closures' => $closureAnnouncementService->relevant()
                ->with('creator')
                ->get(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
        ]);
        $dayOfWeek = (int) $validated['day_of_week'];

        DB::transaction(function () use ($dayOfWeek, $validated, $request): void {
            $daySchedules = $this->lockDaySchedules($dayOfWeek);
            $this->ensureNoOverlap($daySchedules, $validated['opening_time'], $validated['closing_time']);

            OperatingSchedule::query()->create([
                'day_of_week' => $dayOfWeek,
                'opening_time' => $validated['opening_time'],
                'closing_time' => $validated['closing_time'],
                'is_open' => true,
                'updated_by' => $request->user()->user_id,
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.operating-schedules.index')
            ->with('success', 'Operating interval added.');
    }

    public function update(Request $request, OperatingSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'is_open' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($schedule, $validated, $request): void {
            $daySchedules = $this->lockDaySchedules((int) $schedule->day_of_week);
            $lockedSchedule = $daySchedules->firstWhere('schedule_id', $schedule->schedule_id);
            abort_if($lockedSchedule === null, 404);
            $isOpen = (bool) $validated['is_open'];

            if ($isOpen) {
                $this->ensureNoOverlap(
                    $daySchedules,
                    $validated['opening_time'],
                    $validated['closing_time'],
                    $lockedSchedule->schedule_id,
                );
            }

            $lockedSchedule->opening_time = $validated['opening_time'];
            $lockedSchedule->closing_time = $validated['closing_time'];
            $lockedSchedule->is_open = $isOpen;
            $lockedSchedule->updated_by = $request->user()->user_id;
            $lockedSchedule->updated_at = now();
            $lockedSchedule->save();
        });

        return redirect()
            ->route('admin.operating-schedules.index')
            ->with('success', 'Operating interval updated.');
    }

    public function closeDay(Request $request, int $dayOfWeek): RedirectResponse
    {
        abort_unless(array_key_exists($dayOfWeek, self::DAY_NAMES), 404);

        DB::transaction(function () use ($dayOfWeek, $request): void {
            $daySchedules = $this->lockDaySchedules($dayOfWeek);

            foreach ($daySchedules as $schedule) {
                $schedule->is_open = false;
                $schedule->updated_by = $request->user()->user_id;
                $schedule->updated_at = now();
                $schedule->save();
            }
        });

        return redirect()
            ->route('admin.operating-schedules.index')
            ->with('success', self::DAY_NAMES[$dayOfWeek].' marked closed.');
    }

    public function closuresIndex(): View
    {
        return view('gym-closures.index', [
            'closures' => GymClosure::query()
                ->with('creator')
                ->orderByDesc('start_datetime')
                ->orderByDesc('closure_id')
                ->paginate(15),
        ]);
    }

    public function createClosure(): View
    {
        return view('gym-closures.form', [
            'closure' => null,
        ]);
    }

    public function storeClosure(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->closureRules());
        GymClosure::query()->create([
            ...$validated,
            'closure_status' => $this->closureStatus($validated['start_datetime'], $validated['end_datetime']),
            'created_by' => $request->user()->user_id,
        ]);

        return redirect()
            ->route('admin.gym-closures.index')
            ->with('success', 'Closure announcement created.');
    }

    public function editClosure(GymClosure $closure): View
    {
        return view('gym-closures.form', [
            'closure' => $closure,
        ]);
    }

    public function updateClosure(Request $request, GymClosure $closure): RedirectResponse
    {
        $validated = $request->validate($this->closureRules());
        $closure->update([
            ...$validated,
            'closure_status' => $this->closureStatus($validated['start_datetime'], $validated['end_datetime']),
        ]);

        return redirect()
            ->route('admin.gym-closures.index')
            ->with('success', 'Closure announcement updated.');
    }

    private function lockDaySchedules(int $dayOfWeek): EloquentCollection
    {
        return OperatingSchedule::query()
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('opening_time')
            ->lockForUpdate()
            ->get();
    }

    private function ensureNoOverlap(
        EloquentCollection $daySchedules,
        string $openingTime,
        string $closingTime,
        ?int $exceptScheduleId = null,
    ): void {
        $openingMinute = $this->timeInMinutes($openingTime);
        $closingMinute = $this->timeInMinutes($closingTime);

        foreach ($daySchedules as $schedule) {
            if (! $schedule->is_open || $schedule->schedule_id === $exceptScheduleId) {
                continue;
            }

            $existingOpeningMinute = $this->timeInMinutes($schedule->opening_time);
            $existingClosingMinute = $this->timeInMinutes($schedule->closing_time);

            if ($openingMinute < $existingClosingMinute && $closingMinute > $existingOpeningMinute) {
                throw ValidationException::withMessages([
                    'opening_time' => 'This interval overlaps another open interval for that day.',
                ]);
            }
        }
    }

    private function timeInMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }

    private function closureRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'reason' => ['nullable', 'string', 'max:10000'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
        ];
    }

    private function closureStatus(string $startDateTime, string $endDateTime): string
    {
        $start = Carbon::parse($startDateTime);
        $end = Carbon::parse($endDateTime);

        if ($end->lessThanOrEqualTo(now())) {
            return 'Completed';
        }

        return $start->lessThanOrEqualTo(now())
            ? 'Active'
            : 'Scheduled';
    }

    private function routePrefix(Request $request): string
    {
        return match ($request->user()->role) {
            'Staff' => 'staff.',
            'Member' => 'member.',
            default => 'admin.',
        };
    }
}
