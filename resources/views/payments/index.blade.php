@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Payments')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Payments</p>
<div class="page-heading">
    <div>
        <h1>Payments</h1>
        <p>Search recorded payments and view their membership and refund history.</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'payments.create') }}">Record Payment</a>
</div>

<section class="card">
    <form class="filters" method="GET" action="{{ route($routePrefix.'payments.index') }}">
        <label for="search">Search</label>
        <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Member, email, plan, or reference">
        <label for="method">Method</label>
        <select id="method" name="method">
            <option value="">All</option>
            @foreach (['Cash', 'GCash', 'Card', 'Other'] as $option)
                <option value="{{ $option }}" @selected($method === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit">Apply</button>
        @if ($search !== '' || $method !== '')
            <a class="button secondary" href="{{ route($routePrefix.'payments.index') }}">Clear filters</a>
        @endif
    </form>
</section>

<section class="card">
    @if ($payments->isEmpty())
        <p>No payments match this filter.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Date</th><th>Member</th><th>Membership</th><th>Amount</th><th>Method</th><th>Reference</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M j, Y g:i A') }}</td>
                        <td>{{ $payment->membership->member->first_name }} {{ $payment->membership->member->last_name }}<br>{{ $payment->membership->member->user->email }}</td>
                        <td>{{ $payment->membership->plan_name_snapshot }}</td>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->payment_method }}</td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td><a href="{{ route($routePrefix.'payments.show', $payment) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $payments->links() }}</div>
    @endif
</section>
@endsection
