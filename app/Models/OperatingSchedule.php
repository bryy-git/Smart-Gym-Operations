<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatingSchedule extends Model
{
    public $timestamps = false;

    protected $table = 'operating_schedules';

    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_open',
        'updated_by',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_open' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
