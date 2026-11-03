@extends('layouts.admin')

@section('title', 'Review Refund')

@section('content')
<p class="breadcrumb"><a href="{{ route('admin.refunds.index') }}">Refund Requests</a> / Review</p>
<h1>Review Refund Request #{{ $refund->refund_id }}</h1>
<section class="card">
    <dl>
        <dt>Payment</dt><dd><a href="{{ route('admin.payments.show', $refund->payment) }}">#{{ $refund->payment_id }}</a></dd>
        <dt>Member</dt><dd>{{ $refund->payment->membership->member->first_name }} {{ $refund->payment->membership->member->last_name }}</dd>
        <dt>Original payment amount</dt><dd>{{ number_format((float) $refund->payment->amount, 2) }}</dd>
        <dt>Previously approved refunds</dt><dd>{{ number_format((float) $refund->payment->amount - (float) $remainingBalance, 2) }}</dd>
        <dt>Remaining refundable balance</dt><dd>{{ number_format((float) $remainingBalance, 2) }}</dd>
        <dt>Requested amount</dt><dd>{{ number_format((float) $refund->requested_amount, 2) }}</dd>
        <dt>Reason</dt><dd>{{ $refund->reason }}</dd>
        <dt>Requested by</dt><dd>{{ $refund->requester->first_name }} {{ $refund->requester->last_name }}</dd>
    </dl>
</section>

<section class="card">
    <h2>Approve Refund</h2>
    @if ((float) $remainingBalance <= 0)
        <p class="warning">No refundable balance remains. This request cannot be approved.</p>
    @else
        <p>Admin may approve an amount up to the requested amount and remaining payment balance. The system records the refund; it does not transfer money.</p>
        <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}">
            @csrf
            <div class="field">
                <label for="approved_amount">Approved Amount</label>
                <input id="approved_amount" name="approved_amount" type="number" min="0.01" max="{{ min((float) $refund->requested_amount, (float) $remainingBalance) }}" step="0.01" value="{{ old('approved_amount', min((float) $refund->requested_amount, (float) $remainingBalance)) }}" required>
            </div>
            <div class="field">
                <label for="approval_reason">Decision Note (optional)</label>
                <textarea id="approval_reason" name="decision_reason" rows="2" maxlength="2000">{{ old('decision_reason') }}</textarea>
            </div>
            <button type="submit">Approve Refund</button>
        </form>
    @endif
</section>

<section class="card">
    <h2>Reject Refund</h2>
    <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}">
        @csrf
        <div class="field">
            <label for="rejection_reason">Decision Reason (optional)</label>
            <textarea id="rejection_reason" name="decision_reason" rows="2" maxlength="2000">{{ old('decision_reason') }}</textarea>
        </div>
        <button class="danger" type="submit">Reject Refund</button>
    </form>
</section>
@endsection
