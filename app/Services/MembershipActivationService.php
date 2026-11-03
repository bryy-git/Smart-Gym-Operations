<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\MembershipRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MembershipActivationService
{
    public function activate(
        Member $member,
        MembershipPlan $plan,
        User $receiver,
        string $paymentMethod,
        ?string $referenceNumber,
        ?string $notes,
        ?Membership $renewalOf = null,
        ?MembershipRequest $requestBeingApproved = null,
    ): Membership {
        $lockedMember = Member::query()
            ->whereKey($member->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        $user = User::query()
            ->whereKey($lockedMember->user_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedMember->member_status !== 'Active' || $user->account_status !== 'Active') {
            throw ValidationException::withMessages([
                'member_id' => 'Memberships can only be assigned to active member accounts.',
            ]);
        }

        $lockedPlan = MembershipPlan::query()
            ->whereKey($plan->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedPlan->plan_status !== 'Active') {
            throw ValidationException::withMessages([
                'plan_id' => 'Only active membership plans can be assigned.',
            ]);
        }

        $today = today();
        $startDate = $today->copy();

        if ($renewalOf === null) {
            $hasCurrentOrUpcomingMembership = $lockedMember->memberships()
                ->where('membership_status', 'Active')
                ->whereDate('end_date', '>=', $today->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($hasCurrentOrUpcomingMembership) {
                throw ValidationException::withMessages([
                    'member_id' => 'This member already has an active or upcoming membership. Submit a renewal instead.',
                ]);
            }

            $pendingPurchase = $lockedMember->membershipRequests()
                ->where('request_type', 'Purchase')
                ->where('request_status', 'Pending')
                ->when(
                    $requestBeingApproved !== null,
                    fn ($query) => $query->where('request_id', '!=', $requestBeingApproved->request_id),
                )
                ->lockForUpdate()
                ->exists();

            if ($pendingPurchase) {
                throw ValidationException::withMessages([
                    'member_id' => 'A purchase request is already pending. Review that request instead of assigning another membership.',
                ]);
            }
        } else {
            $lockedRenewal = Membership::query()
                ->whereKey($renewalOf->getKey())
                ->where('member_id', $lockedMember->member_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedRenewal->membership_status, ['Active', 'Expired'], true)) {
                throw ValidationException::withMessages([
                    'membership_id' => 'Only active or expired memberships can be renewed.',
                ]);
            }

            $hasOtherCurrentMembership = $lockedMember->memberships()
                ->where('membership_id', '!=', $lockedRenewal->membership_id)
                ->where('membership_status', 'Active')
                ->whereDate('end_date', '>=', $today->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($hasOtherCurrentMembership) {
                throw ValidationException::withMessages([
                    'membership_id' => 'Renew the member’s current or most recent membership.',
                ]);
            }

            $alreadyRenewed = MembershipRequest::query()
                ->where('membership_id', $lockedRenewal->membership_id)
                ->where('request_type', 'Renewal')
                ->where('request_status', 'Approved')
                ->exists();

            if ($alreadyRenewed) {
                throw ValidationException::withMessages([
                    'membership_id' => 'This membership has already been renewed. Renew the resulting membership instead.',
                ]);
            }

            $pendingRenewal = $lockedRenewal->requests()
                ->where('request_type', 'Renewal')
                ->where('request_status', 'Pending')
                ->when(
                    $requestBeingApproved !== null,
                    fn ($query) => $query->where('request_id', '!=', $requestBeingApproved->request_id),
                )
                ->lockForUpdate()
                ->exists();

            if ($pendingRenewal) {
                throw ValidationException::withMessages([
                    'membership_id' => 'A renewal request is already pending. Review that request instead of assigning another renewal.',
                ]);
            }

            if (
                $lockedRenewal->membership_status === 'Active'
                && $lockedRenewal->end_date->greaterThanOrEqualTo($today)
            ) {
                $startDate = $lockedRenewal->end_date->copy()->addDay();
            } elseif ($lockedRenewal->membership_status === 'Active') {
                $lockedRenewal->membership_status = 'Expired';
                $lockedRenewal->save();
            }
        }

        $lockedMember->memberships()
            ->where('membership_status', 'Active')
            ->whereDate('end_date', '<', $today->toDateString())
            ->lockForUpdate()
            ->get()
            ->each(function (Membership $membership): void {
                $membership->membership_status = 'Expired';
                $membership->save();
            });

        $endDate = $startDate->copy()->addDays($lockedPlan->duration_days - 1);
        $membership = Membership::create([
            'member_id' => $lockedMember->member_id,
            'plan_id' => $lockedPlan->plan_id,
            'plan_name_snapshot' => $lockedPlan->plan_name,
            'plan_price_snapshot' => $lockedPlan->price,
            'plan_duration_snapshot' => $lockedPlan->duration_days,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'membership_status' => 'Active',
            'created_by' => $receiver->user_id,
        ]);

        Payment::create([
            'membership_id' => $membership->membership_id,
            'amount' => $lockedPlan->price,
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'payment_date' => Carbon::now(),
            'received_by' => $receiver->user_id,
            'notes' => $notes,
        ]);

        return $membership;
    }
}
