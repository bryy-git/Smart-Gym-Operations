<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ request()->routeIs('login') ? '#121212' : '#EEEEEE' }}">
    <title>@yield('title', 'Account') | SmartGym Operations</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ request()->routeIs('login') ? 'auth-page' : '' }}">
@unless (request()->routeIs('login'))
<header class="app-header">
    <a class="brand" href="{{ auth()->check() ? route(strtolower(auth()->user()->role).'.dashboard') : route('home') }}">
        @if (request()->routeIs('login'))
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="">
        @else
            <span class="brand-mark" aria-hidden="true">S</span>
        @endif
        SmartGym <span class="muted">Operations</span>
    </a>
    <div class="header-actions">
        @auth
            <span class="user-chip">{{ auth()->user()->role }}@if (auth()->user()->first_name) · {{ auth()->user()->first_name }}@endif</span>
            <a class="button secondary" href="{{ route('profile.edit') }}">Profile</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        @else
            <a href="{{ route('home') }}">Home</a>
            @if (! request()->routeIs('login'))
                <a class="button secondary" href="{{ route('login') }}">Log in</a>
            @endif
        @endauth
    </div>
</header>
@endunless

@auth
    @if (auth()->user()->role === 'Member')
        <nav class="member-nav" aria-label="Member navigation">
            <a href="{{ route('member.dashboard') }}" @if (request()->routeIs('member.dashboard')) aria-current="page" @endif>Overview</a>
            <a href="{{ route('member.plans.index') }}" @if (request()->routeIs('member.plans.*')) aria-current="page" @endif>Plans</a>
            <a href="{{ route('member.memberships.index') }}" @if (request()->routeIs('member.memberships.*')) aria-current="page" @endif>Memberships</a>
            <a href="{{ route('member.membership-requests.index') }}" @if (request()->routeIs('member.membership-requests.*')) aria-current="page" @endif>Requests</a>
            <a href="{{ route('member.attendance.index') }}" @if (request()->routeIs('member.attendance.*')) aria-current="page" @endif>Attendance</a>
            <a href="{{ route('member.payments.index') }}" @if (request()->routeIs('member.payments.*')) aria-current="page" @endif>Payments</a>
            <a href="{{ route('member.notifications.index') }}" @if (request()->routeIs('member.notifications.*')) aria-current="page" @endif>Notifications</a>
            <a href="{{ route('member.operating-schedules.index') }}" @if (request()->routeIs('member.operating-schedules.*')) aria-current="page" @endif>Hours & closures</a>
        </nav>
    @endif
@endauth

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
