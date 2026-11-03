<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    public $timestamps = false;

    protected $table = 'attendance_corrections';

    protected $primaryKey = 'correction_id';

    protected $fillable = [
        'attendance_id',
        'corrected_by',
        'original_check_in_at',
        'corrected_check_in_at',
        'original_check_out_at',
        'corrected_check_out_at',
        'correction_reason',
        'corrected_at',
    ];

    protected function casts(): array
    {
        return [
            'original_check_in_at' => 'datetime',
            'corrected_check_in_at' => 'datetime',
            'original_check_out_at' => 'datetime',
            'corrected_check_out_at' => 'datetime',
            'corrected_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'attendance_id');
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by', 'user_id');
    }
}
