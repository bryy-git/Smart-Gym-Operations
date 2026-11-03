<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Member;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public const MAXIMUM_CAPACITY_SETTING = 'attendance.maximum_capacity';

    public function checkIn(Member $member, string $source): Attendance
    {
        return DB::transaction(function () use ($member, $source): Attendance {
            $lockedMember = Member::query()
                ->whereKey($member->member_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureEligibleForCheckIn($lockedMember);

            if ($lockedMember->attendances()->whereNull('check_out_at')->exists()) {
                throw ValidationException::withMessages([
                    'attendance' => 'This member already has an open attendance session.',
                ]);
            }

            return $lockedMember->attendances()->create([
                'check_in_at' => now(),
                'attendance_source' => $source,
            ]);
        });
    }

    public function checkOut(Attendance $attendance): Attendance
    {
        return DB::transaction(function () use ($attendance): Attendance {
            $member = Member::query()
                ->whereKey($attendance->member_id)
                ->lockForUpdate()
                ->firstOrFail();
            $openAttendance = $member->attendances()
                ->whereKey($attendance->attendance_id)
                ->whereNull('check_out_at')
                ->lockForUpdate()
                ->first();

            if ($openAttendance === null) {
                throw ValidationException::withMessages([
                    'attendance' => 'This member has no open attendance session.',
                ]);
            }

            $checkOutAt = now();
            if ($checkOutAt->lessThanOrEqualTo($openAttendance->check_in_at)) {
                $checkOutAt = $openAttendance->check_in_at->copy()->addSecond();
            }

            $openAttendance->check_out_at = $checkOutAt;
            $openAttendance->save();

            return $openAttendance;
        });
    }

    public function correct(
        Attendance $attendance,
        User $corrector,
        Carbon $checkInAt,
        ?Carbon $checkOutAt,
        string $reason,
    ): Attendance {
        if ($checkOutAt !== null && $checkOutAt->lessThanOrEqualTo($checkInAt)) {
            throw ValidationException::withMessages([
                'check_out_at' => 'The check-out time must be after the check-in time.',
            ]);
        }

        return DB::transaction(function () use (
            $attendance,
            $corrector,
            $checkInAt,
            $checkOutAt,
            $reason,
        ): Attendance {
            $member = Member::query()
                ->whereKey($attendance->member_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAttendance = Attendance::query()
                ->whereKey($attendance->attendance_id)
                ->where('member_id', $member->member_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($checkOutAt === null && $member->attendances()
                ->whereNull('check_out_at')
                ->where('attendance_id', '!=', $lockedAttendance->attendance_id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'check_out_at' => 'This member already has another open attendance session.',
                ]);
            }

            $sameCheckIn = $lockedAttendance->check_in_at->equalTo($checkInAt);
            $sameCheckOut = $lockedAttendance->check_out_at === null
                ? $checkOutAt === null
                : ($checkOutAt !== null && $lockedAttendance->check_out_at->equalTo($checkOutAt));

            if ($sameCheckIn && $sameCheckOut) {
                throw ValidationException::withMessages([
                    'check_in_at' => 'Change at least one attendance timestamp to record a correction.',
                ]);
            }

            AttendanceCorrection::create([
                'attendance_id' => $lockedAttendance->attendance_id,
                'corrected_by' => $corrector->user_id,
                'original_check_in_at' => $lockedAttendance->check_in_at,
                'corrected_check_in_at' => $checkInAt,
                'original_check_out_at' => $lockedAttendance->check_out_at,
                'corrected_check_out_at' => $checkOutAt,
                'correction_reason' => $reason,
                'corrected_at' => now(),
            ]);

            $lockedAttendance->check_in_at = $checkInAt;
            $lockedAttendance->check_out_at = $checkOutAt;
            $lockedAttendance->save();

            return $lockedAttendance;
        });
    }

    public function occupancy(): array
    {
        $settingValue = SystemSetting::query()
            ->where('setting_key', self::MAXIMUM_CAPACITY_SETTING)
            ->value('setting_value');
        $maximumCapacity = filter_var($settingValue, FILTER_VALIDATE_INT);
        $maximumCapacity = $maximumCapacity !== false && $maximumCapacity > 0
            ? $maximumCapacity
            : null;
        $occupancy = Attendance::query()->whereNull('check_out_at')->count();

        return [
            'occupancy' => $occupancy,
            'maximum_capacity' => $maximumCapacity,
            'at_capacity' => $maximumCapacity !== null && $occupancy >= $maximumCapacity,
        ];
    }

    public function setMaximumCapacity(int $maximumCapacity, User $updater): void
    {
        DB::transaction(function () use ($maximumCapacity, $updater): void {
            SystemSetting::query()->updateOrCreate(
                ['setting_key' => self::MAXIMUM_CAPACITY_SETTING],
                [
                    'setting_value' => (string) $maximumCapacity,
                    'description' => 'Maximum occupancy used for monitoring; capacity does not block check-in.',
                    'updated_by' => $updater->user_id,
                    'updated_at' => now(),
                ],
            );
        });
    }

    private function ensureEligibleForCheckIn(Member $member): void
    {
        $member->loadMissing('user');

        if ($member->member_status !== 'Active' || $member->user?->account_status !== 'Active') {
            throw ValidationException::withMessages([
                'member_id' => 'Only members with an active account may check in.',
            ]);
        }

        $hasValidMembership = $member->memberships()
            ->where('membership_status', 'Active')
            ->whereDate('start_date', '<=', today()->toDateString())
            ->whereDate('end_date', '>=', today()->toDateString())
            ->exists();

        if (! $hasValidMembership) {
            throw ValidationException::withMessages([
                'member_id' => 'The member needs an active membership valid today to check in.',
            ]);
        }
    }
}
