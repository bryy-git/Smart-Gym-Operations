@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Membership Details')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'memberships.index') }}">Memberships</a> / Membership Details</p>
<h1>{{ $membership->plan_name_snapshot }} — {{ $membership->member->first_name }} {{ $membership->member->last_name }}</h1>
<section class="card">
    <dl>
        <dt>Member</dt><dd>{{ $membership->member->first_name }} {{ $membership->member->last_name }}</dd>
        <dt>Email</dt><dd>{{ $membership->member->user->email }}</dd>
        <dt>Plan snapshot</dt><dd>{{ $membership->plan_name_snapshot }}</dd>
        <dt>Plan price snapshot</dt><dd>{{ number_format((float) $membership->plan_price_snapshot, 2) }}</dd>
        <dt>Duration snapshot</dt><dd>{{ $membership->plan_duration_snapshot }} days</dd>
        <dt>Start date</dt><dd>{{ $membership->start_date->format('M j, Y') }}</dd>
        <dt>End date</dt><dd>{{ $membership->end_date->format('M j, Y') }}</dd>
        <dt>Status</dt><dd>{{ $membership->display_status }}</dd>
        <dt>Assigned by</dt><dd>{{ $membership->creator?->first_name }} {{ $membership->creator?->last_name }}</dd>
        <dt>Current plan status</dt><dd>{{ $membership->plan?->plan_status ?? 'Unavailable' }}</dd>
    </dl>
</section>

<section class="card">
    <h2>Payments</h2>
    @if ($membership->payments->isEmpty())
        <p>No payment records are linked to this membership.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Received by</th></tr></thead>
                <tbody>
                @foreach ($membership->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M j, Y g:i A') }}</td>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->payment_method }}</td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td>{{ $payment->receiver?->first_name }} {{ $payment->receiver?->last_name }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="card">
    <h2>Membership Requests</h2>
    @if ($membership->requests->isEmpty())
        <p>No membership requests are linked to this record.</p>
    @else
        <ul>
            @foreach ($membership->requests as $membershipRequest)
                <li>{{ $membershipRequest->request_type }} — {{ $membershipRequest->request_status }} — {{ $membershipRequest->requested_at->format('M j, Y g:i A') }}</li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
