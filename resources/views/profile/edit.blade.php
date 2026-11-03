@extends('layouts.account')

@section('title', 'My profile')

@section('content')
    <h1>My profile</h1>
    <form method="post" action="{{ route('profile.update') }}" class="card">
        @csrf
        @method('put')
        <div class="field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="100" autocomplete="given-name">
        </div>
        <div class="field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="100" autocomplete="family-name">
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="190" autocomplete="email">
        </div>
        @if ($member)
            <div class="field">
                <label for="phone">Phone</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $member->phone) }}" required maxlength="30" autocomplete="tel">
            </div>
            <div class="field">
                <label for="date_of_birth">Date of birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $member->date_of_birth?->format('Y-m-d')) }}">
            </div>
        @endif
        <button type="submit">Save profile</button>
    </form>

    <form method="post" action="{{ route('profile.password.update') }}" class="card">
        @csrf
        @method('put')
        <h2>Change password</h2>
        <div class="field">
            <label for="current_password">Current password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label for="new_password">New password</label>
            <input id="new_password" name="password" type="password" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
        <button type="submit">Change password</button>
    </form>
@endsection
