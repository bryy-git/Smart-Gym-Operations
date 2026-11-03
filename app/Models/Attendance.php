<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'member_id',
        'check_in_at',
        'check_out_at',
        'attendance_source',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'attendance_id', 'attendance_id');
    }
}
