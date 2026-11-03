<?php

namespace App\Services;

use App\Jobs\DeliverNotificationEmail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?int $membershipId = null,
    ): Notification {
        return DB::transaction(function () use ($user, $type, $title, $message, $membershipId): Notification {
            $notification = Notification::query()->create([
                'user_id' => $user->user_id,
                'notification_type' => $type,
                'title' => $title,
                'message' => $message,
                'is_read' => false,
                'created_at' => now(),
            ]);

            $notification->deliveries()->createMany([
                [
                    'delivery_channel' => 'In-system',
                    'delivery_status' => 'Sent',
                    'sent_at' => now(),
                ],
                [
                    'delivery_channel' => 'Email',
                    'delivery_status' => 'Pending',
                ],
            ]);

            DeliverNotificationEmail::dispatch($notification->notification_id, $membershipId)->afterCommit();

            return $notification;
        });
    }
}
