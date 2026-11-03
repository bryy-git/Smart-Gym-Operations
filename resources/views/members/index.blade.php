@extends($layout)

@section('title', 'Members')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Members</p>
<div class="page-heading">
    <div>
        <h1>Members</h1>
        <p>Manage member profiles and their accounts.</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'members.create') }}">Add Member</a>
</div>

<section class="card" aria-label="Member search and filters">
    <form class="filters" id="member-filters" method="GET" action="{{ route($routePrefix.'members.index') }}">
        <label for="search">Search</label>
        <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name or email">
        <label for="status">Member Status</label>
        <select id="status" name="status">
            <option value="" @selected($status === '')>All</option>
            <option value="Active" @selected($status === 'Active')>Active</option>
            <option value="Inactive" @selected($status === 'Inactive')>Inactive</option>
        </select>
        <button type="submit">Apply</button>
        @if ($hasFilters)
            <a class="button secondary" href="{{ route($routePrefix.'members.index') }}">Clear filters</a>
        @endif
    </form>
    <p id="members-loading" class="muted" role="status" hidden>Loading members...</p>
</section>

<section class="card">
    @if ($members->isEmpty())
        @if ($hasFilters)
            <p>No search results. Try changing or clearing the filters.</p>
        @else
            <p>No members have been added yet.</p>
        @endif
    @else
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th scope="col">Member Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone Number</th>
                    <th scope="col">Member Status</th>
                    <th scope="col">User Account Status</th>
                    <th scope="col">Registration Date</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($members as $member)
                    @php
                        $statusMismatch = !(
                            ($member->member_status === 'Active' && $member->user->account_status === 'Active')
                            || ($member->member_status === 'Inactive' && $member->user->account_status === 'Disabled')
                        );
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route($routePrefix.'members.show', $member) }}">
                                {{ $member->first_name }} {{ $member->last_name }}
                            </a>
                            @if ($statusMismatch)
                                <div class="warning" role="status">Member and user account statuses do not match.</div>
                            @endif
                        </td>
                        <td>{{ $member->user->email }}</td>
                        <td>{{ $member->phone }}</td>
                        <td><span class="status">{{ $member->member_status }}</span></td>
                        <td><span class="status">{{ $member->user->account_status }}</span></td>
                        <td>{{ $member->created_at?->format('M j, Y') ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route($routePrefix.'members.show', $member) }}">View</a>
                                <a href="{{ route($routePrefix.'members.edit', $member) }}">Edit</a>
                                @if ($member->member_status !== 'Inactive' || $member->user->account_status !== 'Disabled')
                                    <a href="{{ route($routePrefix.'members.status.confirm', [$member, 'Inactive']) }}">Deactivate</a>
                                @endif
                                @if ($member->member_status !== 'Active' || $member->user->account_status !== 'Active')
                                    <a href="{{ route($routePrefix.'members.status.confirm', [$member, 'Active']) }}">Reactivate</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $members->links() }}</div>
    @endif
</section>
<script>
    document.getElementById('member-filters')?.addEventListener('submit', function () {
        document.getElementById('members-loading').hidden = false;
    });
</script>
@endsection
