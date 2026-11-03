<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembershipPlanController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
        ]);
        $routePrefix = $this->routePrefix($request);

        $plans = MembershipPlan::query()
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('plan_status', $validated['status']),
            )
            ->orderBy('plan_name')
            ->paginate(10)
            ->appends($request->query());

        return view('membership-plans.index', [
            'routePrefix' => $routePrefix,
            'plans' => $plans,
            'status' => $validated['status'] ?? '',
        ]);
    }

    public function catalog(Request $request): View
    {
        $member = Member::query()
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        $hasCurrentOrUpcomingMembership = $member->memberships()
            ->where('membership_status', 'Active')
            ->whereDate('end_date', '>=', today()->toDateString())
            ->exists();

        return view('membership-plans.catalog', [
            'plans' => MembershipPlan::query()
                ->where('plan_status', 'Active')
                ->orderBy('plan_name')
                ->paginate(10),
            'hasCurrentOrUpcomingMembership' => $hasCurrentOrUpcomingMembership,
        ]);
    }

    public function create(Request $request): View
    {
        return view('membership-plans.form', [
            'routePrefix' => $this->routePrefix($request),
            'plan' => null,
        ]);
    }

    public function show(Request $request, MembershipPlan $plan): View
    {
        $plan->loadCount(['memberships', 'requests']);

        return view('membership-plans.show', [
            'routePrefix' => $this->routePrefix($request),
            'plan' => $plan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_name' => ['required', 'string', 'max:120', Rule::unique('membership_plans', 'plan_name')],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
        ]);

        $plan = MembershipPlan::create([
            ...$validated,
            'plan_status' => 'Active',
        ]);

        return redirect()
            ->route($this->routePrefix($request).'membership-plans.index')
            ->with('success', 'Membership plan created successfully.');
    }

    public function edit(Request $request, MembershipPlan $plan): View
    {
        return view('membership-plans.form', [
            'routePrefix' => $this->routePrefix($request),
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, MembershipPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'plan_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('membership_plans', 'plan_name')->ignore($plan->plan_id, 'plan_id'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
        ]);

        DB::transaction(function () use ($plan, $validated): void {
            $lockedPlan = MembershipPlan::query()
                ->whereKey($plan->plan_id)
                ->lockForUpdate()
                ->firstOrFail();
            $hasPendingRequests = $lockedPlan->requests()
                ->where('request_status', 'Pending')
                ->exists();
            $planDetailsChanged = $lockedPlan->plan_name !== $validated['plan_name']
                || $lockedPlan->duration_days !== (int) $validated['duration_days']
                || number_format((float) $lockedPlan->price, 2, '.', '')
                    !== number_format((float) $validated['price'], 2, '.', '');

            if ($hasPendingRequests && $planDetailsChanged) {
                throw ValidationException::withMessages([
                    'plan_name' => 'Plan name, duration, and price cannot change while requests for this plan are pending.',
                ]);
            }

            $lockedPlan->update($validated);
        });

        return redirect()
            ->route($this->routePrefix($request).'membership-plans.index')
            ->with('success', 'Membership plan updated successfully.');
    }

    public function updateStatus(Request $request, MembershipPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'plan_status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $plan->plan_status = $validated['plan_status'];
        $plan->save();

        return redirect()
            ->route($this->routePrefix($request).'membership-plans.index')
            ->with('success', 'Membership plan status updated successfully.');
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'staff.')
            ? 'staff.'
            : 'admin.';
    }
}
