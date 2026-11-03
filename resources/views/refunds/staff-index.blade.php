@extends('layouts.staff')

@section('title', 'My Refund Requests')

@section('content')
<p class="breadcrumb">Staff / Refund Requests</p>
<h1>My Refund Requests</h1>
<section class="card">
    @if ($refunds->isEmpty())
        <p>You have not submitted any refund requests.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Payment</th><th>Member</th><th>Reason</th><th>Requested</th><th>Approved</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($refunds as $refund)
                    <tr>
                        <td>#{{ $refund->payment_id }}</td>
                        <td>{{ $refund->payment->membership->member->first_name }} {{ $refund->payment->membership->member->last_name }}</td>
                        <td>{{ $refund->reason }}</td>
                        <td>{{ number_format((float) $refund->requested_amount, 2) }}</td>
                        <td>{{ $refund->approved_amount === null ? '—' : number_format((float) $refund->approved_amount, 2) }}</td>
                        <td>{{ $refund->refund_status }}</td>
                        <td><a href="{{ route('staff.refunds.show', $refund) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $refunds->links() }}</div>
    @endif
</section>
@endsection
