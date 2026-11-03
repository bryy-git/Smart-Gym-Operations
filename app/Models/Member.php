<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $table = 'members';

    protected $primaryKey = 'member_id';

    protected $fillable = [
        'user_id',
        'application_id',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'member_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'member_id', 'member_id');
    }

    public function membershipRequests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class, 'member_id', 'member_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'member_id', 'member_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(OnlineApplication::class, 'application_id', 'application_id');
    }
}
