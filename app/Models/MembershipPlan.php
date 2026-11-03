<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';

    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_name',
        'description',
        'duration_days',
        'price',
        'plan_status',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'plan_id', 'plan_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class, 'plan_id', 'plan_id');
    }
}
