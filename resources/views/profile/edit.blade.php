@extends('layouts.account')

@section('title', 'Edit profile')

@section('content')
    <div class="profile-edit-page">
        <div class="profile-edit-heading">
            <div>
                <p class="eyebrow">Account settings</p>
                <h1>Edit profile</h1>
                <p class="muted">Update your personal information. Your current password is required to confirm changes.</p>
            </div>
            <a class="button secondary" href="{{ route('profile.show') }}">View profile</a>
        </div>

        <form method="post" action="{{ route('profile.update') }}" class="card profile-form">
            @csrf
            @method('put')
            <div class="section-heading">
                <div>
                    <h2>Personal information</h2>
                    <p>Keep your contact details accurate and up to date.</p>
                </div>
                <span class="profile-status">Account active</span>
            </div>
            <div class="profile-fields-grid">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="100" autocomplete="given-name">
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="100" autocomplete="family-name">
                </div>
                <div class="field profile-field-full">
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
                <div class="field profile-field-full">
                    <label for="profile_current_password">Current password</label>
                    <input id="profile_current_password" name="current_password" type="password" required autocomplete="current-password">
                    <small>Required to save profile changes.</small>
                </div>
            </div>
            <div class="profile-form-actions">
                <button class="secondary" type="submit">Save profile</button>
            </div>
        </form>

        <form method="post" action="{{ route('profile.password.update') }}" class="card profile-form">
            @csrf
            @method('put')
            <div class="section-heading">
                <div>
                    <h2>Change password</h2>
                    <p>Use a unique password with at least 12 characters.</p>
                </div>
            </div>
            <div class="profile-fields-grid">
                <div class="field profile-field-full">
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
            </div>
            <div class="profile-form-actions">
                <button class="secondary" type="submit">Change password</button>
            </div>
        </form>

        <p class="profile-last-updated">Last updated {{ $user->updated_at?->format('F j, Y \a\t g:i A') ?: 'Not available' }}</p>
    </div>
@endsection