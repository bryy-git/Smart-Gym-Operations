<!DOCTYPE html>
<html lang="en" @if ($audience === 'Admin') class="admin-document" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $audience === 'Admin' ? '#121212' : '#EEEEEE' }}">
    <title>@yield('title', 'Operations') | SmartGym {{ $audience }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $audience === 'Admin' ? 'admin-area' : '' }}{{ $audience === 'Admin' && request()->routeIs('admin.dashboard') ? ' admin-dashboard' : '' }}">
<header class="app-header">
    <a class="brand" href="{{ route(strtolower($audience).'.dashboard') }}"><img class="brand-logo" src="{{ asset('images/logo.png') }}" alt=""> SmartGym <span class="muted">Operations</span></a>
    <button class="mobile-sidebar-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="mobile-navigation">
        <span class="hamburger-icon" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
    <div class="header-actions">

        <details class="user-menu">
            <summary class="user-menu-trigger">
                <span class="user-menu-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="presentation">
                        <circle cx="12" cy="8" r="3.25"></circle>
                        <path d="M5.5 20c.55-3.25 2.72-5 6.5-5s5.95 1.75 6.5 5"></path>
                    </svg>
                </span>
                <span class="user-menu-identity">
                    <strong>{{ auth()->user()->first_name ?: $audience }}</strong>
                    <span>{{ $audience }}</span>
                </span>
            </summary>
            <div class="user-menu-panel">
                <a href="{{ route('profile.edit') }}">View profile</a>
                <a href="{{ route('profile.edit') }}">Edit profile</a>
                <form method="post" action="{{ route('logout') }}" data-confirm="true" data-confirm-title="Log out?" data-confirm-message="You will be signed out of SmartGym Operations." data-confirm-label="Log out">
                    @csrf
                    <button class="logout-action" type="submit">Log out</button>
                </form>
            </div>
        </details>
    </div>
</header>
<div class="mobile-sidebar" id="mobile-navigation" hidden>
    @include('layouts.partials.operations-navigation', ['audience' => $audience])
</div>
<div class="app-shell">
    <aside class="sidebar" aria-label="Sidebar">
        @include('layouts.partials.operations-navigation', ['audience' => $audience])
    </aside>
    <div class="main-column">
        <main class="main-content">
    @if (session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="error" role="alert"><strong>Error:</strong> {{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="error" role="alert">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
        </main>
    </div>
</div>
</body>
</html>


