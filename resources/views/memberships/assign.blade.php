@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Assign Paid Membership')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'memberships.index') }}">Memberships</a> / Assign Paid Membership</p>
<h1>Assign Paid Membership</h1>
<section class="card">
    <p>A successful assignment records the full active-plan price as a payment in the same transaction. Select a previous membership to process this as a renewal.</p>
    @if ($members->isEmpty())
        <p class="warning">No active member accounts are available for assignment.</p>
    @elseif ($plans->isEmpty())
        <p class="warning">No active membership plans are available.</p>
    @else
        <form method="POST" action="{{ route($routePrefix.'memberships.assign') }}">
            @csrf
            <div class="field">
                <label for="member_id">Member</label>
                <select id="member_id" name="member_id" required>
                    <option value="">Select a member</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->member_id }}" @selected((string) old('member_id') === (string) $member->member_id)>
                            {{ $member->first_name }} {{ $member->last_name }} — {{ $member->user->email }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="plan_id">Active Plan</label>
                <select id="plan_id" name="plan_id" required>
                    <option value="">Select a plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->plan_id }}" @selected((string) old('plan_id') === (string) $plan->plan_id)>
                            {{ $plan->plan_name }} — {{ $plan->duration_days }} days — {{ number_format((float) $plan->price, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="membership_id">Renewal of (optional)</label>
                <select id="membership_id" name="membership_id">
                    <option value="">New purchase</option>
                    @foreach ($renewableMemberships as $membership)
                        <option value="{{ $membership->membership_id }}" @selected((string) old('membership_id') === (string) $membership->membership_id)>
                            {{ $membership->member->first_name }} {{ $membership->member->last_name }} — {{ $membership->plan_name_snapshot }} (ends {{ $membership->end_date->format('M j, Y') }})
                        </option>
                    @endforeach
                </select>
                <p class="muted">The selected previous membership must belong to the chosen member and must not be suspended or already renewed.</p>
            </div>
            <div class="field">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">Select a method</option>
                    @foreach (['Cash', 'GCash', 'Card', 'Other'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="reference_number">Reference Number (optional)</label>
                <input id="reference_number" name="reference_number" maxlength="150" value="{{ old('reference_number') }}">
            </div>
            <div class="field">
                <label for="payment_notes">Payment Notes (optional)</label>
                <textarea id="payment_notes" name="payment_notes" rows="3" maxlength="5000">{{ old('payment_notes') }}</textarea>
            </div>
            <div class="field">
                <label>
                    <input type="checkbox" name="payment_confirmed" value="1" required @checked(old('payment_confirmed'))>
                    I confirm that the member has paid the full price of the selected plan.
                </label>
            </div>
            <div class="button-row">
                <button type="submit">Record Payment and Assign</button>
                <a class="button secondary" href="{{ route($routePrefix.'memberships.index') }}">Cancel</a>
            </div>
        </form>
    @endif
</section>
@endsection
