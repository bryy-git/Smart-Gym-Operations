<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $primaryKey = 'equipment_id';

    protected $fillable = [
        'equipment_name',
        'description',
        'equipment_status',
        'usage_threshold',
    ];

    protected function casts(): array
    {
        return [
            'usage_threshold' => 'integer',
        ];
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'equipment_id', 'equipment_id');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(EquipmentUsage::class, 'equipment_id', 'equipment_id');
    }
}
