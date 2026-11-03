@extends($layout)

@php
    $creating = $mode === 'create';
    $pageTitle = $creating ? 'Add Member' : 'Edit Member';
    $action = $creating
        ? route($routePrefix.'members.store')
        : route($routePrefix.'members.update', $member);
@endphp

@section('title', $pageTitle)

@section('content')
<p class="breadcrumb">
    <a href="{{ route($routePrefix.'members.index') }}">Members</a> /
    @if (!$creating)
        <a href="{{ route($routePrefix.'members.show', $member) }}">{{ $member->first_name }} {{ $member->last_name }}</a> /
    @endif
    {{ $pageTitle }}
</p>
<div class="page-heading"><h1>{{ $pageTitle }}</h1></div>
<section class="card">
    <form method="POST" action="{{ $action }}">
        @csrf
        @unless ($creating)
            @method('PUT')
        @endunless
        <div class="field">
            <label for="first_name">First Name</label>
            <input id="first_name" name="first_name" value="{{ old('first_name', $member?->first_name ?? '') }}" maxlength="100" required autocomplete="given-name">
        </div>
        <div class="field">
            <label for="last_name">Last Name</label>
            <input id="last_name" name="last_name" value="{{ old('last_name', $member?->last_name ?? '') }}" maxlength="100" required autocomplete="family-name">
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user?->email ?? '') }}" maxlength="190" required autocomplete="email">
        </div>
        @if ($creating)
            <div class="field">
                <label for="password">Initial Password</label>
                <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password">
            </div>
        @endif
        <div class="field">
            <label for="phone">Phone Number</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $member?->phone ?? '') }}" maxlength="30" required autocomplete="tel">
        </div>
        <div class="field">
            <label for="date_of_birth">Date of Birth (optional)</label>
            <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $member?->date_of_birth?->format('Y-m-d') ?? '') }}" max="{{ now()->toDateString() }}">
        </div>
        <div class="button-row">
            <button type="submit">{{ $creating ? 'Create Member' : 'Save Changes' }}</button>
            <a class="button secondary" href="{{ $creating ? route($routePrefix.'members.index') : route($routePrefix.'members.show', $member) }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
