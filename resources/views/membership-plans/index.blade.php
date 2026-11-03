@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Membership Plans')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Membership Plans</p>
<div class="page-heading">
    <div>
        <h1>Membership Plans</h1>
        <p>Manage plan pricing and availability. Existing memberships retain their plan snapshots.</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'membership-plans.create') }}">Add Plan</a>
</div>

<section class="card">
    <form class="filters" method="GET" action="{{ route($routePrefix.'membership-plans.index') }}">
        <label for="status">Plan Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <option value="Active" @selected($status === 'Active')>Active</option>
            <option value="Inactive" @selected($status === 'Inactive')>Inactive</option>
        </select>
        <button type="submit">Filter</button>
        @if ($status !== '')
            <a class="button secondary" href="{{ route($routePrefix.'membership-plans.index') }}">Clear filter</a>
        @endif
    </form>
</section>

<section class="card">
    @if ($plans->isEmpty())
        <p>No membership plans match this filter.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th scope="col">Plan</th>
                    <th scope="col">Duration</th>
                    <th scope="col">Price</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($plans as $plan)
                    <tr>
                        <td>
                            <a href="{{ route($routePrefix.'membership-plans.show', $plan) }}"><strong>{{ $plan->plan_name }}</strong></a>
                            @if ($plan->description)
                                <div>{{ $plan->description }}</div>
                            @endif
                        </td>
                        <td>{{ $plan->duration_days }} days</td>
                        <td>{{ number_format((float) $plan->price, 2) }}</td>
                        <td><span class="status">{{ $plan->plan_status }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route($routePrefix.'membership-plans.edit', $plan) }}">Edit</a>
                                <form method="POST" action="{{ route($routePrefix.'membership-plans.status.update', $plan) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="plan_status" value="{{ $plan->plan_status === 'Active' ? 'Inactive' : 'Active' }}">
                                    <button class="button {{ $plan->plan_status === 'Active' ? 'danger' : 'success' }}" type="submit">
                                        {{ $plan->plan_status === 'Active' ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $plans->links() }}</div>
    @endif
</section>
@endsection
