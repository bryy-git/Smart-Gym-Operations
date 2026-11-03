<?php

namespace App\Jobs;

use App\Mail\NotificationEmail;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $notificationId,
        public readonly ?int $membershipId = null,
    ) {}

    public function handle(): void
    {
        $notification = Notification::query()
            ->with('user')
            ->find($this->notificationId);

        if ($notification === null) {
            return;
        }

        $delivery = $notification->deliveries()
            ->where('delivery_channel', 'Email')
            ->firstOrFail();

        if ($delivery->delivery_status === 'Sent') {
            return;
        }

        if ($this->membershipId !== null && ! $this->membershipIsCurrent($notification->user_id)) {
            $delivery->update([
                'delivery_status' => 'Failed',
                'sent_at' => null,
                'error_details' => 'Not sent because the membership is no longer current.',
            ]);

            return;
        }

        $delivery->update([
            'delivery_status' => 'Pending',
            'sent_at' => null,
            'error_details' => null,
        ]);

        try {
            Mail::to($notification->user->email)->send(new NotificationEmail(
                $notification->title,
                $notification->message,
            ));
        } catch (Throwable $exception) {
            $delivery->update([
                'delivery_status' => 'Failed',
                'error_details' => 'Email delivery failed ('.$exception::class.').',
            ]);
            report($exception);

            throw $exception;
        }

        $delivery->update([
            'delivery_status' => 'Sent',
            'sent_at' => now(),
            'error_details' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        NotificationDelivery::query()
            ->where('notification_id', $this->notificationId)
            ->where('delivery_channel', 'Email')
            ->where('delivery_status', '!=', 'Sent')
            ->update([
                'delivery_status' => 'Failed',
                'error_details' => 'Email delivery failed after queue retries.',
            ]);
    }

    private function membershipIsCurrent(int $userId): bool
    {
        $membership = Membership::query()
            ->whereKey($this->membershipId)
            ->where('membership_status', 'Active')
            ->whereDate('start_date', '<=', today()->toDateString())
            ->whereDate('end_date', '>=', today()->toDateString())
            ->whereHas('member', fn (Builder $members): Builder => $members->where('user_id', $userId))
            ->whereNotExists(function (QueryBuilder $renewals): void {
                $renewals->selectRaw('1')
                    ->from('memberships as renewal_memberships')
                    ->whereColumn('renewal_memberships.member_id', 'memberships.member_id')
                    ->where('renewal_memberships.membership_status', 'Active')
                    ->whereColumn('renewal_memberships.start_date', '>', 'memberships.end_date');
            })
            ->exists();

        return $membership;
    }
}
