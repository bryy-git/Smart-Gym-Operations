@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Memberships')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Memberships</p>
<div class="page-heading">
    <div>
        <h1>Memberships</h1>
        <p>Membership records include payment-backed activations and historical plan snapshots.</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'memberships.assign.create') }}">Assign Paid Membership</a>
</div>

<section class="card">
    <form class="filters" method="GET" action="{{ route($routePrefix.'memberships.index') }}">
        <label for="search">Search</label>
        <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Member, email, or plan">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            @foreach (['Active', 'Upcoming', 'Expired', 'Suspended'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit">Apply</button>
        @if ($search !== '' || $status !== '')
            <a class="button secondary" href="{{ route($routePrefix.'memberships.index') }}">Clear filters</a>
        @endif
    </form>
</section>

<section class="card">
    @if ($memberships->isEmpty())
        <p>No memberships match this filter.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th scope="col">Member</th>
                    <th scope="col">Plan Snapshot</th>
                    <th scope="col">Dates</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($memberships as $membership)
                    <tr>
                        <td>{{ $membership->member->first_name }} {{ $membership->member->last_name }}<br>{{ $membership->member->user->email }}</td>
                        <td>{{ $membership->plan_name_snapshot }}<br>{{ number_format((float) $membership->plan_price_snapshot, 2) }} · {{ $membership->plan_duration_snapshot }} days</td>
                        <td>{{ $membership->start_date->format('M j, Y') }} – {{ $membership->end_date->format('M j, Y') }}</td>
                        <td><span class="status">{{ $membership->display_status }}</span></td>
                        <td><a href="{{ route($routePrefix.'memberships.show', $membership) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $memberships->links() }}</div>
    @endif
</section>
@endsection
