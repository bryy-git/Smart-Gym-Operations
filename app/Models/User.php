<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'account_status',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'user_id', 'user_id');
    }

    public function requestedMembershipRequests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class, 'requested_by', 'user_id');
    }

    public function reviewedMembershipRequests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class, 'reviewed_by', 'user_id');
    }

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by', 'user_id');
    }

    public function requestedRefunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'requested_by', 'user_id');
    }

    public function reviewedRefunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'reviewed_by', 'user_id');
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'corrected_by', 'user_id');
    }

    public function reportedMaintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'reported_by', 'user_id');
    }

    public function updatedMaintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'updated_by', 'user_id');
    }

    public function recordedEquipmentUsages(): HasMany
    {
        return $this->hasMany(EquipmentUsage::class, 'recorded_by', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function updatedSystemSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'updated_by', 'user_id');
    }

    public function createdBackups(): HasMany
    {
        return $this->hasMany(BackupRecord::class, 'created_by', 'user_id');
    }

    public function restoredBackups(): HasMany
    {
        return $this->hasMany(BackupRecord::class, 'restored_by', 'user_id');
    }

    public function updatedOperatingSchedules(): HasMany
    {
        return $this->hasMany(OperatingSchedule::class, 'updated_by', 'user_id');
    }

    public function createdGymClosures(): HasMany
    {
        return $this->hasMany(GymClosure::class, 'created_by', 'user_id');
    }

    public function reviewedOnlineApplications(): HasMany
    {
        return $this->hasMany(OnlineApplication::class, 'reviewed_by', 'user_id');
    }
}
