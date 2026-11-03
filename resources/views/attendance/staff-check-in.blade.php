@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Staff-assisted Check-in')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'attendance.index') }}">Attendance</a> / Staff-assisted check-in</p>
<h1>Staff-assisted check-in</h1>
<section class="card">
    @if ($members->isEmpty())
        <p>No members with an active account and valid membership are available to check in.</p>
    @else
        <form method="POST" action="{{ route($routePrefix.'attendance.check-in') }}">
            @csrf
            <div class="field">
                <label for="member_id">Member</label>
                <select id="member_id" name="member_id" required>
                    <option value="">Select a member</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->member_id }}" @selected((string) old('member_id') === (string) $member->member_id)>
                            {{ $member->first_name }} {{ $member->last_name }} — {{ $member->user->email }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="button-row">
                <button type="submit">Check in member</button>
                <a class="button secondary" href="{{ route($routePrefix.'attendance.index') }}">Cancel</a>
            </div>
        </form>
    @endif
</section>
@endsection
