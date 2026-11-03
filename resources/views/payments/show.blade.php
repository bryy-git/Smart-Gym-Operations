@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Payment Details')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'payments.index') }}">Payments</a> / Payment Details</p>
<div class="page-heading">
    <h1>Payment #{{ $payment->payment_id }}</h1>
    <div class="button-row">
        <a class="button secondary" href="{{ route($routePrefix.'payments.receipt', $payment) }}">Printable Receipt</a>
        @if ($routePrefix === 'staff.' && (float) $remainingBalance > 0)
            <a class="button danger" href="{{ route('staff.payments.refunds.create', $payment) }}">Request Refund</a>
        @endif
    </div>
</div>
<section class="card">
    <dl>
        <dt>Member</dt><dd>{{ $payment->membership->member->first_name }} {{ $payment->membership->member->last_name }} — {{ $payment->membership->member->user->email }}</dd>
        <dt>Membership</dt><dd><a href="{{ route($routePrefix.'memberships.show', $payment->membership) }}">{{ $payment->membership->plan_name_snapshot }}</a></dd>
        <dt>Amount</dt><dd>{{ number_format((float) $payment->amount, 2) }}</dd>
        <dt>Payment Method</dt><dd>{{ $payment->payment_method }}</dd>
        <dt>Reference Number</dt><dd>{{ $payment->reference_number ?? '—' }}</dd>
        <dt>Payment Date</dt><dd>{{ $payment->payment_date->format('M j, Y g:i A') }}</dd>
        <dt>Received By</dt><dd>{{ $payment->receiver?->first_name }} {{ $payment->receiver?->last_name }}</dd>
        <dt>Notes</dt><dd>{{ $payment->notes ?? '—' }}</dd>
        <dt>Approved Refunds</dt><dd>{{ number_format((float) $approvedRefundTotal, 2) }}</dd>
        <dt>Remaining Refund Balance</dt><dd>{{ number_format((float) $remainingBalance, 2) }}</dd>
    </dl>
</section>

<section class="card">
    <h2>Refund History</h2>
    @if ($payment->refunds->isEmpty())
        <p>No refund requests have been submitted for this payment.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Requested</th><th>Reason</th><th>Status</th><th>Requested Amount</th><th>Approved Amount</th><th>Requester</th></tr></thead>
                <tbody>
                @foreach ($payment->refunds as $refund)
                    <tr>
                        <td>{{ $refund->created_at?->format('M j, Y') ?? '—' }}</td>
                        <td>{{ $refund->reason }}</td>
                        <td>{{ $refund->refund_status }}</td>
                        <td>{{ number_format((float) $refund->requested_amount, 2) }}</td>
                        <td>{{ $refund->approved_amount === null ? '—' : number_format((float) $refund->approved_amount, 2) }}</td>
                        <td>{{ $refund->requester?->first_name }} {{ $refund->requester?->last_name }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
