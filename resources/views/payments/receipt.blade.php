<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#EEEEEE">
    <title>Payment Receipt #{{ $payment->payment_id }} | SmartGym Operations</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .receipt { max-width: 760px; margin: 2rem auto; padding: 1.5rem; }
        .receipt-details { display: grid; grid-template-columns: minmax(9rem, 12rem) minmax(0, 1fr); gap: .8rem 1rem; }
        dt { font-weight: 700; }
        dd { margin: 0; }
        .receipt-actions { margin-top: 2rem; }
        @media print {
            body { background: white; }
            .receipt { max-width: none; margin: 0; }
            .receipt-actions { display: none; }
        }
        @media (max-width: 520px) {
            .receipt-details { grid-template-columns: 1fr; gap: .25rem; }
            .receipt-details dd { margin-bottom: .7rem; }
        }
    </style>
</head>
<body>
<main class="receipt">
<p class="muted">SmartGym Operations · {{ $audience }} copy</p>
<h1>Payment Receipt</h1>
<p>Receipt #{{ $payment->payment_id }}</p>
<dl class="receipt-details">
    <dt>Member</dt><dd>{{ $payment->membership->member->first_name }} {{ $payment->membership->member->last_name }}</dd>
    <dt>Email</dt><dd>{{ $payment->membership->member->user->email }}</dd>
    <dt>Membership Plan</dt><dd>{{ $payment->membership->plan_name_snapshot }}</dd>
    <dt>Payment Amount</dt><dd>{{ number_format((float) $payment->amount, 2) }}</dd>
    <dt>Payment Method</dt><dd>{{ $payment->payment_method }}</dd>
    <dt>Reference Number</dt><dd>{{ $payment->reference_number ?? '—' }}</dd>
    <dt>Payment Date</dt><dd>{{ $payment->payment_date->format('M j, Y g:i A') }}</dd>
    <dt>Received By</dt><dd>{{ $payment->receiver?->first_name }} {{ $payment->receiver?->last_name }}</dd>
</dl>
<div class="receipt-actions button-row">
    <button type="button" onclick="window.print()">Print Receipt</button>
    @if ($audience === 'Member')
        <a href="{{ route('member.payments.index') }}">Back to payment history</a>
    @elseif ($audience === 'Staff')
        <a href="{{ route('staff.payments.index') }}">Back to payments</a>
    @else
        <a href="{{ route('admin.payments.index') }}">Back to payments</a>
    @endif
</div>
</main>
</body>
</html>
