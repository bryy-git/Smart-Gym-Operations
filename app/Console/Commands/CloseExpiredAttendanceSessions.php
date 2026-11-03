<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\OperatingSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CloseExpiredAttendanceSessions extends Command
{
    protected $signature = 'attendance:auto-close';

    protected $description = 'Close open attendance sessions at the applicable gym closing time.';

    public function handle(): int
    {
        $schedules = OperatingSchedule::query()
            ->where('is_open', true)
            ->get()
            ->groupBy('day_of_week');
        $now = now();
        $closedCount = 0;

        Attendance::query()
            ->whereNull('check_out_at')
            ->where('check_in_at', '<=', $now)
            ->orderBy('attendance_id')
            ->chunkById(200, function (Collection $attendances) use ($schedules, $now, &$closedCount): void {
                foreach ($attendances as $attendance) {
                    $checkInAt = $attendance->check_in_at;
                    $scheduleOptions = $schedules->get($checkInAt->dayOfWeekIso, collect());
                    $closingSchedule = $this->nextClosingSchedule($scheduleOptions, $checkInAt);

                    if ($closingSchedule === null) {
                        continue;
                    }

                    $closingAt = $checkInAt->copy()
                        ->startOfDay()
                        ->setTimeFromTimeString($closingSchedule->closing_time);

                    if ($closingAt->greaterThan($now)) {
                        continue;
                    }

                    $closedCount += Attendance::query()
                        ->whereKey($attendance->attendance_id)
                        ->whereNull('check_out_at')
                        ->update([
                            'check_out_at' => $closingAt,
                            'updated_at' => now(),
                        ]);
                }
            }, 'attendance_id');

        $this->info("Closed {$closedCount} attendance session(s).");

        return self::SUCCESS;
    }

    private function nextClosingSchedule(Collection $schedules, Carbon $checkInAt): ?OperatingSchedule
    {
        return $schedules
            ->filter(fn (OperatingSchedule $schedule): bool => $schedule->closing_time > $checkInAt->format('H:i:s'))
            ->sortBy('closing_time')
            ->first();
    }
}
