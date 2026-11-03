@extends('layouts.account')

@section('title', 'Payment Details')

@section('content')
<h1>Payment Details</h1>
<section class="card">
    <dl>
        <dt>Receipt</dt><dd>#{{ $payment->payment_id }}</dd>
        <dt>Membership</dt><dd>{{ $payment->membership->plan_name_snapshot }}</dd>
        <dt>Amount</dt><dd>{{ number_format((float) $payment->amount, 2) }}</dd>
        <dt>Approved Refunds</dt><dd>{{ number_format((float) $approvedRefundTotal, 2) }}</dd>
        <dt>Payment Method</dt><dd>{{ $payment->payment_method }}</dd>
        <dt>Reference Number</dt><dd>{{ $payment->reference_number ?? '—' }}</dd>
        <dt>Payment Date</dt><dd>{{ $payment->payment_date->format('M j, Y g:i A') }}</dd>
    </dl>
    <div class="button-row">
        <a class="button" href="{{ route('member.payments.receipt', $payment) }}">Printable Receipt</a>
        <a href="{{ route('member.payments.index') }}">Back to payment history</a>
    </div>
</section>
@endsection
