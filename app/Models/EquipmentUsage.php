<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentUsage extends Model
{
    public $timestamps = false;

    protected $table = 'equipment_usage';

    protected $primaryKey = 'usage_id';

    protected $fillable = [
        'equipment_id',
        'recorded_by',
        'usage_count',
        'usage_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
            'usage_date' => 'date',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }
}
