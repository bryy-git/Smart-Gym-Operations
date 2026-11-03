@extends(request()->routeIs('staff.*') ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Review Membership Request')

@section('content')
<p class="breadcrumb"><a href="{{ route(request()->routeIs('staff.*') ? 'staff.membership-requests.index' : 'admin.membership-requests.index') }}">Membership Requests</a> / Review</p>
<h1>Review {{ $membershipRequest->request_type }} Request</h1>

<section class="card">
    <dl>
        <dt>Member</dt><dd>{{ $membershipRequest->member->first_name }} {{ $membershipRequest->member->last_name }} — {{ $membershipRequest->member->user->email }}</dd>
        <dt>Requested plan</dt><dd>{{ $membershipRequest->plan?->plan_name ?? 'Unavailable' }}</dd>
        <dt>Current plan price</dt><dd>{{ $membershipRequest->plan ? number_format((float) $membershipRequest->plan->price, 2) : 'Unavailable' }}</dd>
        <dt>Duration</dt><dd>{{ $membershipRequest->plan?->duration_days ?? 'Unavailable' }} days</dd>
        @if ($membershipRequest->membership)
            <dt>Renewal of</dt><dd>{{ $membershipRequest->membership->plan_name_snapshot }} — ends {{ $membershipRequest->membership->end_date->format('M j, Y') }}</dd>
        @endif
        <dt>Requested</dt><dd>{{ $membershipRequest->requested_at->format('M j, Y g:i A') }}</dd>
        <dt>Member note</dt><dd>{{ $membershipRequest->request_reason ?? '—' }}</dd>
    </dl>
    @if (! $membershipRequest->plan || $membershipRequest->plan->plan_status !== 'Active')
        <p class="warning" role="alert">This plan is no longer active. The request cannot be approved, but it may be rejected with a reason.</p>
    @endif
</section>

<section class="card">
    <h2>Approve and Record Payment</h2>
    <p>Approval records a payment for the full current plan price. Confirm receipt before submitting; the membership will be activated in the same database transaction.</p>
    <form method="POST" action="{{ route(request()->routeIs('staff.*') ? 'staff.membership-requests.approve' : 'admin.membership-requests.approve', $membershipRequest) }}">
        @csrf
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
                I confirm that payment in the full plan amount has been received.
            </label>
        </div>
        <div class="field">
            <label for="approval_reason">Decision Note (optional)</label>
            <textarea id="approval_reason" name="decision_reason" rows="2" maxlength="2000">{{ old('decision_reason') }}</textarea>
        </div>
        <button type="submit">Confirm Payment and Approve</button>
    </form>
</section>

<section class="card">
    <h2>Reject Request</h2>
    <form method="POST" action="{{ route(request()->routeIs('staff.*') ? 'staff.membership-requests.reject' : 'admin.membership-requests.reject', $membershipRequest) }}">
        @csrf
        <div class="field">
            <label for="rejection_reason">Decision Reason (optional)</label>
            <textarea id="rejection_reason" name="decision_reason" rows="2" maxlength="2000">{{ old('decision_reason') }}</textarea>
        </div>
        <button class="danger" type="submit">Reject Request</button>
    </form>
</section>
@endsection
