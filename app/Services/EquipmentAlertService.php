<?php

namespace App\Services;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class EquipmentAlertService
{
    public function withUsageSinceMaintenance(): Builder
    {
        $lastResolvedMaintenance = DB::table('maintenance_records')
            ->select('equipment_id')
            ->selectRaw('MAX(DATE(resolved_at)) AS last_resolved_date')
            ->where('maintenance_status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->groupBy('equipment_id');

        $usageSinceMaintenance = DB::table('equipment_usage')
            ->leftJoinSub(
                $lastResolvedMaintenance,
                'last_resolved_maintenance',
                'last_resolved_maintenance.equipment_id',
                '=',
                'equipment_usage.equipment_id',
            )
            ->where(function (QueryBuilder $query): void {
                $query->whereNull('last_resolved_maintenance.last_resolved_date')
                    ->orWhereColumn(
                        'equipment_usage.usage_date',
                        '>',
                        'last_resolved_maintenance.last_resolved_date',
                    );
            })
            ->select('equipment_usage.equipment_id')
            ->selectRaw('SUM(equipment_usage.usage_count) AS usage_since_maintenance')
            ->groupBy('equipment_usage.equipment_id');

        return Equipment::query()
            ->leftJoinSub(
                $usageSinceMaintenance,
                'usage_totals',
                'usage_totals.equipment_id',
                '=',
                'equipment.equipment_id',
            )
            ->select('equipment.*')
            ->selectRaw('COALESCE(usage_totals.usage_since_maintenance, 0) AS usage_since_maintenance');
    }

    public function alerts(): Builder
    {
        return $this->withUsageSinceMaintenance()
            ->where(function (Builder $query): void {
                $query->where('equipment.equipment_status', '!=', 'Available')
                    ->orWhereHas('maintenanceRecords', function (Builder $maintenance): void {
                        $maintenance->whereIn('maintenance_status', ['Reported', 'In Progress']);
                    })
                    ->orWhere(function (Builder $threshold): void {
                        $threshold->whereNotNull('equipment.usage_threshold')
                            ->whereRaw(
                                'COALESCE(usage_totals.usage_since_maintenance, 0) >= equipment.usage_threshold',
                            );
                    });
            });
    }
}
