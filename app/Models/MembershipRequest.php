<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipRequest extends Model
{
    public $timestamps = false;

    protected $table = 'membership_requests';

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'member_id',
        'membership_id',
        'plan_id',
        'requested_by',
        'request_type',
        'request_status',
        'request_reason',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'membership_id', 'membership_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id', 'plan_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
