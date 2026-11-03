<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    public $timestamps = false;

    protected $table = 'notifications';

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'notification_type',
        'title',
        'message',
        'is_read',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_id', 'notification_id');
    }
}
