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
        <dt>Date of Birth</dt><dd>{{ $member->date_of_birth?->format('M j, Y') ?? '—' }}</dd>
        <dt>Member Status</dt><dd>{{ $member->member_status }}</dd>
        <dt>User Account Status</dt><dd>{{ $user->account_status }}</dd>
        <dt>Registration Date</dt><dd>{{ $member->created_at?->format('M j, Y') ?? '—' }}</dd>
    </dl>
</section>
@endsection
