@extends('layouts.account')

@section('title', 'My Memberships')

@section('content')
<h1>My Memberships</h1>
<p>Review membership history and track purchase or renewal requests.</p>
<p><a class="button" href="{{ route('member.plans.index') }}">Browse Active Plans</a></p>

@if ($canRequestRenewal && $latestMembership && $plans->isNotEmpty())
    <section class="card">
        <h2>Request a Renewal</h2>
        <p>Current membership: {{ $latestMembership->plan_name_snapshot }} (ends {{ $latestMembership->end_date->format('M j, Y') }}). Early renewals begin the day after expiry; expired renewals begin on payment date.</p>
        <form method="POST" action="{{ route('member.memberships.renew', $latestMembership) }}">
            @csrf
            <div class="field">
                <label for="renewal_plan_id">Plan</label>
                <select id="renewal_plan_id" name="plan_id" required>
                    <option value="">Select an active plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->plan_id }}" @selected((string) old('plan_id') === (string) $plan->plan_id)>
                            {{ $plan->plan_name }} — {{ $plan->duration_days }} days — {{ number_format((float) $plan->price, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="renewal_reason">Note (optional)</label>
                <textarea id="renewal_reason" name="request_reason" rows="2" maxlength="2000">{{ old('request_reason') }}</textarea>
            </div>
            <button type="submit">Request Renewal</button>
        </form>
    </section>
@elseif ($latestMembership && ! $canRequestRenewal)
    <section class="card"><p>A renewal request for your latest membership is already pending or approved.</p></section>
@endif

<section class="card">
    <h2>Membership History</h2>
    @if ($memberships->isEmpty())
        <p>No membership records are available.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Plan Snapshot</th><th>Dates</th><th>Status</th><th>Paid</th></tr></thead>
                <tbody>
                @foreach ($memberships as $membership)
                    <tr>
                        <td>{{ $membership->plan_name_snapshot }}<br>{{ number_format((float) $membership->plan_price_snapshot, 2) }} · {{ $membership->plan_duration_snapshot }} days</td>
                        <td>{{ $membership->start_date->format('M j, Y') }} – {{ $membership->end_date->format('M j, Y') }}</td>
                        <td>{{ $membership->display_status }}</td>
                        <td>{{ number_format((float) ($membership->paid_total ?? 0), 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $memberships->links() }}</div>
    @endif
</section>

<section class="card">
    <h2>My Requests</h2>
    @if ($requests->isEmpty())
        <p>No purchase or renewal requests have been submitted.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Type</th><th>Plan</th><th>Note</th><th>Requested</th><th>Status</th><th>Decision</th></tr></thead>
                <tbody>
                @foreach ($requests as $membershipRequest)
                    <tr>
                        <td>{{ $membershipRequest->request_type }}</td>
                        <td>{{ $membershipRequest->plan?->plan_name ?? 'Unavailable' }}</td>
                        <td>{{ $membershipRequest->request_reason ?? '—' }}</td>
                        <td>{{ $membershipRequest->requested_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $membershipRequest->request_status }}</td>
                        <td>{{ $membershipRequest->decision_reason ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $requests->links() }}</div>
    @endif
</section>
@endsection
