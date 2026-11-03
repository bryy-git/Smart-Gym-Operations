@extends('layouts.admin')

@section('title', 'Refund Requests')

@section('content')
<p class="breadcrumb">Admin / Refund Requests</p>
<h1>Refund Requests</h1>
<section class="card">
    <form class="filters" method="GET" action="{{ route('admin.refunds.index') }}">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            @foreach (['Pending', 'Approved', 'Rejected'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>
</section>
<section class="card">
    @if ($refunds->isEmpty())
        <p>No refund requests match this filter.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Payment</th><th>Member</th><th>Requested By</th><th>Reason</th><th>Requested</th><th>Approved</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($refunds as $refund)
                    <tr>
                        <td><a href="{{ route('admin.payments.show', $refund->payment) }}">#{{ $refund->payment_id }}</a></td>
                        <td>{{ $refund->payment->membership->member->first_name }} {{ $refund->payment->membership->member->last_name }}</td>
                        <td>{{ $refund->requester->first_name }} {{ $refund->requester->last_name }}</td>
                        <td>{{ $refund->reason }}</td>
                        <td>{{ number_format((float) $refund->requested_amount, 2) }}</td>
                        <td>{{ $refund->approved_amount === null ? '—' : number_format((float) $refund->approved_amount, 2) }}</td>
                        <td>{{ $refund->refund_status }}</td>
                        <td>
                            @if ($refund->refund_status === 'Pending')
                                <a href="{{ route('admin.refunds.review', $refund) }}">Review</a>
                            @else
                                <a href="{{ route('admin.refunds.show', $refund) }}">View</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $refunds->links() }}</div>
    @endif
</section>
@endsection
