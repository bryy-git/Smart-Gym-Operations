<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    public $timestamps = false;

    protected $table = 'maintenance_records';

    protected $primaryKey = 'maintenance_id';

    protected $fillable = [
        'equipment_id',
        'reported_by',
        'issue_description',
        'maintenance_status',
        'action_details',
        'reported_at',
        'resolved_at',
        'updated_by',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
