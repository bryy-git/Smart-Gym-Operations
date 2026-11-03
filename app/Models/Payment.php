<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    public $timestamps = false;

    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'membership_id',
        'amount',
        'payment_method',
        'reference_number',
        'payment_date',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'membership_id', 'membership_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'payment_id', 'payment_id');
    }
}
