@extends($audience === 'Staff' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Refund Details')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'refunds.index') }}">Refund Requests</a> / Refund Details</p>
<h1>Refund Request #{{ $refund->refund_id }}</h1>
<section class="card">
    <dl>
        <dt>Payment</dt><dd><a href="{{ route($routePrefix.'payments.show', $refund->payment) }}">#{{ $refund->payment_id }}</a></dd>
        <dt>Member</dt><dd>{{ $refund->payment->membership->member->first_name }} {{ $refund->payment->membership->member->last_name }}</dd>
        <dt>Original payment amount</dt><dd>{{ number_format((float) $refund->payment->amount, 2) }}</dd>
        <dt>Remaining refundable balance</dt><dd>{{ number_format((float) $remainingBalance, 2) }}</dd>
        <dt>Requested amount</dt><dd>{{ number_format((float) $refund->requested_amount, 2) }}</dd>
        <dt>Approved amount</dt><dd>{{ $refund->approved_amount === null ? '—' : number_format((float) $refund->approved_amount, 2) }}</dd>
        <dt>Reason</dt><dd>{{ $refund->reason }}</dd>
        <dt>Status</dt><dd>{{ $refund->refund_status }}</dd>
        <dt>Requested by</dt><dd>{{ $refund->requester->first_name }} {{ $refund->requester->last_name }}</dd>
        <dt>Reviewed by</dt><dd>{{ $refund->reviewer?->first_name }} {{ $refund->reviewer?->last_name }}</dd>
        <dt>Reviewed at</dt><dd>{{ $refund->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</dd>
        <dt>Decision reason</dt><dd>{{ $refund->decision_reason ?? '—' }}</dd>
    </dl>
    @if ($audience === 'Admin' && $refund->refund_status === 'Pending')
        <a class="button" href="{{ route('admin.refunds.review', $refund) }}">Review Request</a>
    @endif
</section>
@endsection
