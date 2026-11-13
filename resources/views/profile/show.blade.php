@extends('layouts.account')

@section('title', 'View profile')

@section('content')
    <div class="profile-view">
        <div class="profile-view-heading">
            <div>
                <p class="eyebrow">Account</p>
                <h1>My profile</h1>
                <p class="muted">Review your account information and membership details.</p>
            </div>
            <a class="button" href="{{ route('profile.edit') }}">Edit profile</a>
        </div>
        <section class="profile-summary-card" aria-label="Profile information">
            <div class="profile-avatar" aria-hidden="true">{{ strtoupper(substr($user->first_name ?: 'U', 0, 1)) }}</div>
            <div class="profile-summary">
                <h2>{{ $user->first_name }} {{ $user->last_name }}</h2>
                <p>{{ $user->email }}</p>
                <span class="profile-role">{{ $user->role }}</span>
            </div>
        </section>
        <section class="profile-details-grid" aria-label="Account details">
            <div><span>First name</span><strong>{{ $user->first_name }}</strong></div>
            <div><span>Last name</span><strong>{{ $user->last_name }}</strong></div>
            <div><span>Email</span><strong>{{ $user->email }}</strong></div>
            <div><span>Role</span><strong>{{ $user->role }}</strong></div>
            @if ($member)
                <div><span>Phone</span><strong>{{ $member->phone ?: 'Not provided' }}</strong></div>
                <div><span>Date of birth</span><strong>{{ $member->date_of_birth?->format('F j, Y') ?: 'Not provided' }}</strong></div>
            @endif
        </section>
    </div>
@endsection