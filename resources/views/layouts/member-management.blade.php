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
        <span class="user-chip">{{ $audience }}@if (auth()->user()->first_name) · {{ auth()->user()->first_name }}@endif</span>
        <a class="button secondary" href="{{ route('profile.edit') }}">Profile</a>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
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
