<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    protected $table = 'memberships';

    protected $primaryKey = 'membership_id';

    protected $fillable = [
        'member_id',
        'plan_id',
        'plan_name_snapshot',
        'plan_price_snapshot',
        'plan_duration_snapshot',
        'start_date',
        'end_date',
        'membership_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'plan_price_snapshot' => 'decimal:2',
            'plan_duration_snapshot' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->membership_status !== 'Active') {
            return $this->membership_status;
        }

        if ($this->start_date->isAfter(today())) {
            return 'Upcoming';
        }

        if ($this->end_date->isBefore(today())) {
            return 'Expired';
        }

        return 'Active';
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id', 'plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class, 'membership_id', 'membership_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'membership_id', 'membership_id');
    }
}
