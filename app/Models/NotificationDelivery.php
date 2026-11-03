<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    public $timestamps = false;

    protected $table = 'notification_deliveries';

    protected $primaryKey = 'delivery_id';

    protected $fillable = [
        'notification_id',
        'delivery_channel',
        'delivery_status',
        'sent_at',
        'error_details',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'notification_id', 'notification_id');
    }
}
