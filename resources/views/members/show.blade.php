@extends($layout)

@section('title', 'Member Details')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'members.index') }}">Members</a> / Member Details</p>
<div class="page-heading">
    <div>
        <h1>{{ $member->first_name }} {{ $member->last_name }}</h1>
        <p>Member details</p>
    </div>
    <div class="button-row">
        <a class="button" href="{{ route($routePrefix.'members.edit', $member) }}">Edit</a>
        @if ($member->member_status !== 'Inactive' || $user->account_status !== 'Disabled')
            <a class="button danger" href="{{ route($routePrefix.'members.status.confirm', [$member, 'Inactive']) }}">Deactivate</a>
        @endif
        @if ($member->member_status !== 'Active' || $user->account_status !== 'Active')
            <a class="button success" href="{{ route($routePrefix.'members.status.confirm', [$member, 'Active']) }}">Reactivate</a>
        @endif
    </div>
</div>
@if ($statusesMismatch)
    <div class="warning" role="alert">
        Member and user account statuses do not match. Review both values before changing status.
    </div>
@endif
<section class="card">
    <dl>
        <dt>Full name</dt><dd>{{ $member->first_name }} {{ $member->last_name }}</dd>
        <dt>Email</dt><dd>{{ $user->email }}</dd>
        <dt>Phone Number</dt><dd>{{ $member->phone }}</dd>
        <dt>Date of Birth</dt><dd>{{ $member->date_of_birth?->format('M j, Y') ?? '&#8212;' }}</dd>
        <dt>Member Status</dt><dd>{{ $member->member_status }}</dd>
        <dt>User Account Status</dt><dd>{{ $user->account_status }}</dd>
        <dt>Registration Date</dt><dd>{{ $member->created_at?->format('M j, Y') ?? '&#8212;' }}</dd>
    </dl>
</section>

<section class="card member-memberships-card" aria-labelledby="member-memberships-title">
    <div class="admin-record-table-heading">
        <div>
            <h2 id="member-memberships-title">Membership history</h2>
            <p>Current and previous membership records for this member.</p>
        </div>
        <a class="button secondary" href="{{ route($routePrefix.'memberships.assign.create').'?member_id=' . $member->member_id }}">Reactivate membership</a>
    </div>
    @if ($memberships->isEmpty())
        <p class="muted">No membership records found for this member.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th scope="col">Plan</th>
                    <th scope="col">Dates</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($memberships as $membership)
                    <tr>
                        <td>
                            <strong>{{ $membership->plan_name_snapshot }}</strong>
                            <span class="member-record-meta">{{ number_format((float) $membership->plan_price_snapshot, 2) }} &middot; {{ $membership->plan_duration_snapshot }} days</span>
                        </td>
                        <td>{{ $membership->start_date->format('M j, Y') }} &#8211; {{ $membership->end_date->format('M j, Y') }}</td>
                        <td><span class="status admin-status-badge admin-status-badge--{{ strtolower(str_replace(' ', '-', $membership->display_status)) }}">{{ $membership->display_status }}</span></td>
                        <td><a href="{{ route($routePrefix.'memberships.show', $membership) }}">View details</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@endsection


