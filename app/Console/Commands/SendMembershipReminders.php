<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class SendMembershipReminders extends Command
{
    protected $signature = 'notifications:membership-reminders';

    protected $description = 'Create and email membership expiration reminders.';

    public function handle(NotificationService $notificationService): int
    {
        $today = today();
        $createdCount = 0;
        $duplicateCount = 0;
        $skippedCount = 0;

        foreach ([7, 3] as $daysRemaining) {
            $expirationDate = $today->copy()->addDays($daysRemaining);

            Membership::query()
                ->with(['member.user'])
                ->where('membership_status', 'Active')
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', $expirationDate->toDateString())
                ->whereHas('member', function (Builder $members): void {
                    $members
                        ->where('member_status', 'Active')
                        ->whereHas('user', fn (Builder $users): Builder => $users->where('account_status', 'Active'));
                })
                ->whereNotExists(function (QueryBuilder $renewals): void {
                    $renewals->selectRaw('1')
                        ->from('memberships as renewal_memberships')
                        ->whereColumn('renewal_memberships.member_id', 'memberships.member_id')
                        ->where('renewal_memberships.membership_status', 'Active')
                        ->whereColumn('renewal_memberships.start_date', '>', 'memberships.end_date');
                })
                ->orderBy('membership_id')
                ->chunkById(100, function ($memberships) use (
                    $notificationService,
                    $daysRemaining,
                    $expirationDate,
                    &$createdCount,
                    &$duplicateCount,
                    &$skippedCount,
                ): void {
                    foreach ($memberships as $membership) {
                        $user = $membership->member->user;
                        $title = 'Membership expires in '.$daysRemaining.' days';
                        $message = 'Your '.$membership->plan_name_snapshot.' membership expires on '
                            .$expirationDate->format('M j, Y').'. Renew your membership to avoid an interruption.';

                        $created = DB::transaction(function () use (
                            $membership,
                            $user,
                            $title,
                            $message,
                            $expirationDate,
                            $notificationService,
                        ): ?bool {
                            $lockedUser = User::query()
                                ->whereKey($user->user_id)
                                ->lockForUpdate()
                                ->firstOrFail();

                            if ($lockedUser->account_status !== 'Active') {
                                return null;
                            }

                            $membershipIsCurrent = Membership::query()
                                ->whereKey($membership->membership_id)
                                ->where('membership_status', 'Active')
                                ->whereDate('end_date', $expirationDate->toDateString())
                                ->whereNotExists(function (QueryBuilder $renewals): void {
                                    $renewals->selectRaw('1')
                                        ->from('memberships as renewal_memberships')
                                        ->whereColumn('renewal_memberships.member_id', 'memberships.member_id')
                                        ->where('renewal_memberships.membership_status', 'Active')
                                        ->whereColumn('renewal_memberships.start_date', '>', 'memberships.end_date');
                                })
                                ->exists();

                            if (! $membershipIsCurrent) {
                                return null;
                            }

                            $alreadyCreated = Notification::query()
                                ->where('user_id', $user->user_id)
                                ->where('notification_type', 'Membership Reminder')
                                ->where('title', $title)
                                ->where('message', $message)
                                ->exists();

                            if ($alreadyCreated) {
                                return false;
                            }

                            $notificationService->create(
                                $user,
                                'Membership Reminder',
                                $title,
                                $message,
                                $membership->membership_id,
                            );

                            return true;
                        });

                        if ($created) {
                            $createdCount++;
                        } else {
                            if ($created === false) {
                                $duplicateCount++;
                            } else {
                                $skippedCount++;
                            }
                        }
                    }
                }, 'membership_id');
        }

        $this->info("Created {$createdCount} reminder(s); suppressed {$duplicateCount} duplicate(s); skipped {$skippedCount} renewed or inactive membership(s).");

        return self::SUCCESS;
    }
}
