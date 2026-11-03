<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymClosure extends Model
{
    protected $table = 'gym_closures';

    protected $primaryKey = 'closure_id';

    protected $fillable = [
        'title',
        'reason',
        'start_datetime',
        'end_datetime',
        'closure_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->end_datetime->lessThanOrEqualTo(now())) {
            return 'Completed';
        }

        return $this->start_datetime->lessThanOrEqualTo(now())
            ? 'Active'
            : 'Scheduled';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
