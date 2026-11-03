<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OnlineApplication extends Model
{
    public $timestamps = false;

    protected $table = 'online_applications';

    protected $primaryKey = 'application_id';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'email_verification_status',
        'application_status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'application_id', 'application_id');
    }
}
