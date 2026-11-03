<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\MembershipRequest;
use App\Services\MembershipActivationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['Active', 'Expired', 'Upcoming', 'Suspended'])],
        ]);
        $routePrefix = $this->routePrefix($request);
        $today = today()->toDateString();

        $memberships = Membership::query()
            ->with(['member.user', 'plan'])
            ->when(
                ! empty($validated['search']),
                function (Builder $query) use ($validated): void {
                    $pattern = '%'.mb_strtolower($validated['search']).'%';
                    $query->where(function (Builder $memberships) use ($pattern): void {
                        $memberships
                            ->whereRaw('LOWER(plan_name_snapshot) LIKE ?', [$pattern])
                            ->orWhereHas('member', function (Builder $members) use ($pattern): void {
                                $members
                                    ->whereRaw('LOWER(first_name) LIKE ?', [$pattern])
                                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$pattern])
                                    ->orWhereHas('user', function (Builder $users) use ($pattern): void {
                                        $users->whereRaw('LOWER(email) LIKE ?', [$pattern]);
                                    });
                            });
                    });
                },
            )
            ->when(($validated['status'] ?? null) === 'Active', function (Builder $query) use ($today): void {
                $query->where('membership_status', 'Active')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            })
            ->when(($validated['status'] ?? null) === 'Expired', function (Builder $query) use ($today): void {
                $query->where(function (Builder $statuses) use ($today): void {
                    $statuses
                        ->where('membership_status', 'Expired')
                        ->orWhere(function (Builder $active) use ($today): void {
                            $active->where('membership_status', 'Active')
                                ->whereDate('end_date', '<', $today);
                        });
                });
            })
            ->when(($validated['status'] ?? null) === 'Upcoming', function (Builder $query) use ($today): void {
                $query->where('membership_status', 'Active')
                    ->whereDate('start_date', '>', $today);
            })
            ->when(($validated['status'] ?? null) === 'Suspended', function (Builder $query): void {
                $query->where('membership_status', 'Suspended');
            })
            ->orderByDesc('start_date')
            ->orderByDesc('membership_id')
            ->paginate(10)
            ->appends($request->query());

        return view('memberships.index', [
            'routePrefix' => $routePrefix,
            'memberships' => $memberships,
            'search' => $validated['search'] ?? '',
            'status' => $validated['status'] ?? '',
        ]);
    }

    public function show(Request $request, Membership $membership): View
    {
        $membership->load([
            'member.user',
            'plan',
            'creator',
            'payments.receiver',
            'requests.requester',
            'requests.reviewer',
        ]);

        return view('memberships.show', [
            'routePrefix' => $this->routePrefix($request),
            'membership' => $membership,
        ]);
    }

    public function assignForm(Request $request): View
    {
        return view('memberships.assign', [
            'routePrefix' => $this->routePrefix($request),
            'members' => Member::query()
                ->with('user')
                ->where('member_status', 'Active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'plans' => MembershipPlan::query()
                ->where('plan_status', 'Active')
                ->orderBy('plan_name')
                ->get(),
            'renewableMemberships' => Membership::query()
                ->with('member')
                ->whereIn('membership_status', ['Active', 'Expired'])
                ->orderByDesc('start_date')
                ->orderByDesc('membership_id')
                ->get(),
        ]);
    }

    public function assign(
        Request $request,
        MembershipActivationService $activationService,
    ): RedirectResponse {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'member_id')],
            'plan_id' => ['required', 'integer', Rule::exists('membership_plans', 'plan_id')],
            'membership_id' => ['nullable', 'integer', Rule::exists('memberships', 'membership_id')],
            'payment_confirmed' => ['required', 'accepted'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash', 'Card', 'Other'])],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'payment_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $membership = DB::transaction(function () use ($validated, $request, $activationService): Membership {
            $member = Member::query()->findOrFail($validated['member_id']);
            $plan = MembershipPlan::query()->findOrFail($validated['plan_id']);
            $renewalOf = empty($validated['membership_id'])
                ? null
                : Membership::query()->findOrFail($validated['membership_id']);

            return $activationService->activate(
                $member,
                $plan,
                $request->user(),
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $validated['payment_notes'] ?? null,
                $renewalOf,
            );
        });

        return redirect()
            ->route($this->routePrefix($request).'memberships.show', $membership)
            ->with('success', 'Paid membership assigned successfully.');
    }

    public function memberIndex(Request $request): View
    {
        $member = $this->memberForUser($request);
        $memberships = $member->memberships()
            ->with('plan')
            ->withSum('payments as paid_total', 'amount')
            ->orderByDesc('start_date')
            ->orderByDesc('membership_id')
            ->paginate(10, ['*'], 'memberships_page');
        $requests = $member->membershipRequests()
            ->with(['plan', 'membership'])
            ->orderByDesc('requested_at')
            ->paginate(10, ['*'], 'requests_page');

        $latestMembership = $member->memberships()
            ->orderByDesc('start_date')
            ->orderByDesc('membership_id')
            ->first();
        $canRequestRenewal = $latestMembership !== null
            && in_array($latestMembership->membership_status, ['Active', 'Expired'], true)
            && ! $latestMembership->requests()
                ->where('request_type', 'Renewal')
                ->whereIn('request_status', ['Pending', 'Approved'])
                ->exists();

        return view('memberships.member-index', [
            'member' => $member,
            'memberships' => $memberships,
            'requests' => $requests,
            'latestMembership' => $latestMembership,
            'canRequestRenewal' => $canRequestRenewal,
            'plans' => MembershipPlan::query()->where('plan_status', 'Active')->orderBy('plan_name')->get(),
        ]);
    }

    public function storePurchaseRequest(
        Request $request,
        MembershipPlan $plan,
    ): RedirectResponse {
        $validated = $request->validate([
            'request_reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $member = $this->memberForUser($request);

        DB::transaction(function () use ($member, $plan, $request, $validated): void {
            $lockedMember = Member::query()
                ->whereKey($member->member_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureMemberCanRequest($lockedMember);

            $lockedPlan = MembershipPlan::query()
                ->whereKey($plan->plan_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensurePlanIsActive($lockedPlan);
            $this->ensureNoCurrentOrUpcomingMembership($lockedMember);

            $hasPendingPurchase = $lockedMember->membershipRequests()
                ->where('request_type', 'Purchase')
                ->where('request_status', 'Pending')
                ->lockForUpdate()
                ->exists();

            if ($hasPendingPurchase) {
                throw ValidationException::withMessages([
                    'plan_id' => 'A purchase request is already pending for this member.',
                ]);
            }

            MembershipRequest::create([
                'member_id' => $lockedMember->member_id,
                'membership_id' => null,
                'plan_id' => $lockedPlan->plan_id,
                'requested_by' => $request->user()->user_id,
                'request_type' => 'Purchase',
                'request_status' => 'Pending',
                'request_reason' => $validated['request_reason'] ?? null,
                'requested_at' => now(),
            ]);
        });

        return redirect()
            ->route('member.memberships.index')
            ->with('status', 'Your purchase request has been submitted for review.');
    }

    public function storeRenewalRequest(
        Request $request,
        Membership $membership,
    ): RedirectResponse {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('membership_plans', 'plan_id')],
            'request_reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $member = $this->memberForUser($request);

        DB::transaction(function () use ($member, $membership, $request, $validated): void {
            $lockedMember = Member::query()
                ->whereKey($member->member_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureMemberCanRequest($lockedMember);

            $renewal = Membership::query()
                ->whereKey($membership->membership_id)
                ->where('member_id', $lockedMember->member_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($renewal->membership_status, ['Active', 'Expired'], true)) {
                throw ValidationException::withMessages([
                    'membership_id' => 'Only active or expired memberships can be renewed.',
                ]);
            }

            $plan = MembershipPlan::query()
                ->whereKey($validated['plan_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensurePlanIsActive($plan);

            $hasOtherCurrentMembership = $lockedMember->memberships()
                ->where('membership_id', '!=', $renewal->membership_id)
                ->where('membership_status', 'Active')
                ->whereDate('end_date', '>=', today()->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($hasOtherCurrentMembership) {
                throw ValidationException::withMessages([
                    'membership_id' => 'Renew the member’s current or most recent membership.',
                ]);
            }

            $alreadyRenewed = $renewal->requests()
                ->where('request_type', 'Renewal')
                ->whereIn('request_status', ['Pending', 'Approved'])
                ->lockForUpdate()
                ->exists();

            if ($alreadyRenewed) {
                throw ValidationException::withMessages([
                    'membership_id' => 'A renewal request is already pending or approved for this membership.',
                ]);
            }

            MembershipRequest::create([
                'member_id' => $lockedMember->member_id,
                'membership_id' => $renewal->membership_id,
                'plan_id' => $plan->plan_id,
                'requested_by' => $request->user()->user_id,
                'request_type' => 'Renewal',
                'request_status' => 'Pending',
                'request_reason' => $validated['request_reason'] ?? null,
                'requested_at' => now(),
            ]);
        });

        return redirect()
            ->route('member.memberships.index')
            ->with('status', 'Your renewal request has been submitted for review.');
    }

    public function requests(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['Pending', 'Approved', 'Rejected'])],
            'type' => ['nullable', Rule::in(['Purchase', 'Renewal'])],
        ]);
        $routePrefix = $this->routePrefix($request);

        $requests = MembershipRequest::query()
            ->with(['member.user', 'plan', 'membership'])
            ->when(
                ! empty($validated['status']),
                fn (Builder $query): Builder => $query->where('request_status', $validated['status']),
            )
            ->when(
                ! empty($validated['type']),
                fn (Builder $query): Builder => $query->where('request_type', $validated['type']),
            )
            ->orderByRaw("CASE WHEN request_status = 'Pending' THEN 0 ELSE 1 END")
            ->orderByDesc('requested_at')
            ->paginate(10)
            ->appends($request->query());

        return view('membership-requests.index', [
            'routePrefix' => $routePrefix,
            'requests' => $requests,
            'status' => $validated['status'] ?? '',
            'type' => $validated['type'] ?? '',
        ]);
    }

    public function memberRequests(Request $request): View
    {
        $member = $this->memberForUser($request);
        $requests = $member->membershipRequests()
            ->with(['plan', 'membership', 'reviewer'])
            ->orderByDesc('requested_at')
            ->paginate(10);

        return view('membership-requests.member-index', [
            'requests' => $requests,
        ]);
    }

    public function review(MembershipRequest $membershipRequest): View
    {
        abort_if($membershipRequest->request_status !== 'Pending', 409);

        $membershipRequest->load(['member.user', 'plan', 'membership']);

        return view('membership-requests.review', [
            'membershipRequest' => $membershipRequest,
        ]);
    }

    public function approve(
        Request $request,
        MembershipRequest $membershipRequest,
        MembershipActivationService $activationService,
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_confirmed' => ['required', 'accepted'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash', 'Card', 'Other'])],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'payment_notes' => ['nullable', 'string', 'max:5000'],
            'decision_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $createdMembership = DB::transaction(function () use (
            $membershipRequest,
            $request,
            $validated,
            $activationService,
        ): Membership {
            $lockedRequest = MembershipRequest::query()
                ->whereKey($membershipRequest->request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->request_status !== 'Pending') {
                abort(409, 'This membership request has already been reviewed.');
            }

            $member = Member::query()->findOrFail($lockedRequest->member_id);
            $plan = MembershipPlan::query()->findOrFail($lockedRequest->plan_id);
            $renewalOf = $lockedRequest->request_type === 'Renewal'
                ? Membership::query()->findOrFail($lockedRequest->membership_id)
                : null;

            $createdMembership = $activationService->activate(
                $member,
                $plan,
                $request->user(),
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $validated['payment_notes'] ?? null,
                $renewalOf,
                $lockedRequest,
            );

            $lockedRequest->request_status = 'Approved';
            if ($lockedRequest->request_type === 'Purchase') {
                $lockedRequest->membership_id = $createdMembership->membership_id;
            }
            $lockedRequest->reviewed_by = $request->user()->user_id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->decision_reason = $validated['decision_reason'] ?? null;
            $lockedRequest->save();

            return $createdMembership;
        });

        return redirect()
            ->route($this->routePrefix($request).'memberships.show', $createdMembership)
            ->with('success', 'Request approved; the membership and payment were recorded.');
    }

    public function reject(Request $request, MembershipRequest $membershipRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($membershipRequest, $request, $validated): void {
            $lockedRequest = MembershipRequest::query()
                ->whereKey($membershipRequest->request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->request_status !== 'Pending') {
                abort(409, 'This membership request has already been reviewed.');
            }

            $lockedRequest->request_status = 'Rejected';
            $lockedRequest->reviewed_by = $request->user()->user_id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->decision_reason = $validated['decision_reason'] ?? null;
            $lockedRequest->save();
        });

        return redirect()
            ->route($this->routePrefix($request).'membership-requests.index')
            ->with('success', 'Membership request rejected.');
    }

    private function memberForUser(Request $request): Member
    {
        return Member::query()
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
    }

    private function ensureMemberCanRequest(Member $member): void
    {
        if ($member->member_status !== 'Active') {
            throw ValidationException::withMessages([
                'member_id' => 'Only active members can submit membership requests.',
            ]);
        }
    }

    private function ensurePlanIsActive(MembershipPlan $plan): void
    {
        if ($plan->plan_status !== 'Active') {
            throw ValidationException::withMessages([
                'plan_id' => 'Only active membership plans can be requested.',
            ]);
        }
    }

    private function ensureNoCurrentOrUpcomingMembership(Member $member): void
    {
        $hasCurrentOrUpcomingMembership = $member->memberships()
            ->where('membership_status', 'Active')
            ->whereDate('end_date', '>=', today()->toDateString())
            ->lockForUpdate()
            ->exists();

        if ($hasCurrentOrUpcomingMembership) {
            throw ValidationException::withMessages([
                'plan_id' => 'You already have an active or upcoming membership. Submit a renewal request instead.',
            ]);
        }
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'staff.')
            ? 'staff.'
            : 'admin.';
    }
}
