@extends('layouts.staff')

@section('title', 'Request Refund')

@section('content')
<p class="breadcrumb"><a href="{{ route('staff.payments.show', $payment) }}">Payment #{{ $payment->payment_id }}</a> / Request Refund</p>
<h1>Request Refund</h1>
<section class="card">
    <dl>
        <dt>Member</dt><dd>{{ $payment->membership->member->first_name }} {{ $payment->membership->member->last_name }}</dd>
        <dt>Payment amount</dt><dd>{{ number_format((float) $payment->amount, 2) }}</dd>
        <dt>Previously approved refunds</dt><dd>{{ number_format((float) $payment->amount - (float) $remainingBalance, 2) }}</dd>
        <dt>Remaining refundable balance</dt><dd>{{ number_format((float) $remainingBalance, 2) }}</dd>
    </dl>
    @if ((float) $remainingBalance <= 0)
        <p>No refundable balance remains for this payment.</p>
    @else
        <form method="POST" action="{{ route('staff.payments.refunds.store', $payment) }}">
            @csrf
            <div class="field">
                <label for="requested_amount">Requested Amount</label>
                <input id="requested_amount" name="requested_amount" type="number" min="0.01" max="{{ $remainingBalance }}" step="0.01" value="{{ old('requested_amount') }}" required>
            </div>
            <div class="field">
                <label for="reason">Refund Reason</label>
                <textarea id="reason" name="reason" rows="4" maxlength="5000" required>{{ old('reason') }}</textarea>
            </div>
            <div class="button-row">
                <button type="submit">Submit Refund Request</button>
                <a class="button secondary" href="{{ route('staff.payments.show', $payment) }}">Cancel</a>
            </div>
        </form>
    @endif
</section>
@endsection
