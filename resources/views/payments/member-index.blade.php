@extends('layouts.account')

@section('title', 'Payment History')

@section('content')
<h1>Payment History</h1>
<section class="card">
    @if ($payments->isEmpty())
        <p>No payment records are available.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Date</th><th>Membership</th><th>Amount</th><th>Method</th><th>Reference</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M j, Y g:i A') }}</td>
                        <td>{{ $payment->membership->plan_name_snapshot }}</td>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->payment_method }}</td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td><a href="{{ route('member.payments.show', $payment) }}">Details</a> · <a href="{{ route('member.payments.receipt', $payment) }}">Receipt</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $payments->links() }}</div>
    @endif
</section>
@endsection
