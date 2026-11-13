<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ request()->routeIs('login') ? '#121212' : '#EEEEEE' }}">
    <title>@yield('title', 'Account') | SmartGym Operations</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ request()->routeIs('login') ? 'auth-page' : (request()->routeIs('profile.*') ? 'profile-page' : (request()->routeIs('member.*') ? 'member-area' : '')) }}">
@unless (request()->routeIs('login'))
<header class="app-header">
    <a class="brand" href="{{ auth()->check() ? route(strtolower(auth()->user()->role).'.dashboard') : route('home') }}">
        <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="SmartGym logo">
        SmartGym <span class="muted">Operations</span>
    </a>
    <div class="header-actions">
        @auth
            <details class="user-menu">
                <summary class="user-menu-trigger">
                    <span class="user-menu-avatar" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="presentation">
                            <circle cx="12" cy="8" r="3.25"></circle>
                            <path d="M5.5 20c.55-3.25 2.72-5 6.5-5s5.95 1.75 6.5 5"></path>
                        </svg>
                    </span>
                    <span class="user-menu-identity">
                        <strong>{{ auth()->user()->first_name ?: auth()->user()->role }}</strong>
                        <span>{{ auth()->user()->role }}</span>
                    </span>
                </summary>
                <div class="user-menu-panel">
                    <a href="{{ route('profile.show') }}">View profile</a>
                    <a href="{{ route('profile.edit') }}">Edit profile</a>
                    <form method="post" action="{{ route('logout') }}" data-confirm="true" data-confirm-title="Log out?" data-confirm-message="You will be signed out of SmartGym Operations." data-confirm-label="Log out">
                        @csrf
                        <button class="logout-action" type="submit">Log out</button>
                    </form>
                </div>
            </details>
        @else
            <a href="{{ route('home') }}">Home</a>
            @if (! request()->routeIs('login'))
                <a class="button secondary" href="{{ route('login') }}">Log in</a>
            @endif
        @endauth
    </div>
</header>
@endunless

@if (auth()->check() && auth()->user()->role === 'Member' && ! request()->routeIs('profile.*'))
    <nav class="member-nav" aria-label="Member navigation">
        <a href="{{ route('member.dashboard') }}" @if (request()->routeIs('member.dashboard')) aria-current="page" @endif>Dashboard</a>
        <a href="{{ route('member.memberships.index') }}" @if (request()->routeIs('member.memberships.*')) aria-current="page" @endif>My Membership</a>
        <a href="{{ route('member.attendance.index') }}" @if (request()->routeIs('member.attendance.*')) aria-current="page" @endif>Attendance</a>
        <a href="{{ route('member.payments.index') }}" @if (request()->routeIs('member.payments.*')) aria-current="page" @endif>Payments</a>
        <a href="{{ route('member.membership-requests.index') }}" @if (request()->routeIs('member.membership-requests.*')) aria-current="page" @endif>Requests</a>
        <a href="{{ route('member.notifications.index') }}" @if (request()->routeIs('member.notifications.*')) aria-current="page" @endif>Notifications</a>
    </nav>
@endif
<main class="{{ request()->routeIs('login', 'password.*') ? 'auth-shell' : 'member-shell' }}{{ request()->routeIs('login') ? ' auth-login-shell' : '' }}">
    <div class="{{ request()->routeIs('login', 'password.*') ? 'auth-card' : '' }}{{ request()->routeIs('login') ? ' auth-login-card' : '' }}">
        @if (session('status'))
            <div class="status-message" role="status">{{ session('status') }}</div>
        @endif
        @if (session('success'))
            <div class="success-message" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="error validation-summary" id="validation-summary" role="alert" aria-labelledby="validation-summary-heading" tabindex="-1">
                <strong id="validation-summary-heading">Please correct the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </div>
</main>
</body>
</html>


