@extends($layout)

@section('title', 'Confirm Status Change')

@section('content')
<p class="breadcrumb">
    <a href="{{ route($routePrefix.'members.index') }}">Members</a> /
    <a href="{{ route($routePrefix.'members.show', $member) }}">{{ $member->first_name }} {{ $member->last_name }}</a> /
    Confirm status change
</p>
<h1>Confirm {{ $targetStatus === 'Inactive' ? 'Deactivation' : 'Reactivation' }}</h1>
<section class="card">
    @if ($statusesMismatch)
        <div class="warning" role="alert">
            The current statuses do not match. Review both current values and explicitly confirm both target statuses.
        </div>
    @endif
    <dl>
        <dt>Member</dt><dd>{{ $member->first_name }} {{ $member->last_name }}</dd>
        <dt>Current Member Status</dt><dd>{{ $member->member_status }}</dd>
        <dt>Current User Account Status</dt><dd>{{ $user->account_status }}</dd>
        <dt>Target Member Status</dt><dd>{{ $targetStatus }}</dd>
        <dt>Target User Account Status</dt><dd>{{ $targetAccountStatus }}</dd>
    </dl>
    <form method="POST" action="{{ route($routePrefix.'members.status.update', [$member, $targetStatus]) }}">
        @csrf
        @method('PATCH')
        <div class="field">
            <label>
                <input type="checkbox" name="confirm_status_change" value="1" required>
                I confirm the member status will be {{ $targetStatus }} and the user account status will be {{ $targetAccountStatus }}.
            </label>
        </div>
        <div class="button-row">
            <button type="submit">Confirm Status Change</button>
            <a class="button secondary" href="{{ route($routePrefix.'members.show', $member) }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
