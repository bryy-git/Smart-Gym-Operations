@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@php
    $creating = $plan === null;
    $pageTitle = $creating ? 'Add Membership Plan' : 'Edit Membership Plan';
    $action = $creating
        ? route($routePrefix.'membership-plans.store')
        : route($routePrefix.'membership-plans.update', $plan);
@endphp

@section('title', $pageTitle)

@section('content')
<p class="breadcrumb">
    <a href="{{ route($routePrefix.'membership-plans.index') }}">Membership Plans</a> / {{ $pageTitle }}
</p>
<h1>{{ $pageTitle }}</h1>
<section class="card">
    <form method="POST" action="{{ $action }}">
        @csrf
        @unless ($creating)
            @method('PUT')
        @endunless
        <div class="field">
            <label for="plan_name">Plan Name</label>
            <input id="plan_name" name="plan_name" value="{{ old('plan_name', $plan?->plan_name ?? '') }}" maxlength="120" required>
        </div>
        <div class="field">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description" rows="4" maxlength="5000">{{ old('description', $plan?->description ?? '') }}</textarea>
        </div>
        <div class="field">
            <label for="duration_days">Duration (days)</label>
            <input id="duration_days" name="duration_days" type="number" min="1" step="1" value="{{ old('duration_days', $plan?->duration_days ?? '') }}" required>
        </div>
        <div class="field">
            <label for="price">Price</label>
            <input id="price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ old('price', $plan?->price ?? '') }}" required>
        </div>
        <p class="muted">Editing a plan does not change snapshots on existing memberships.</p>
        <div class="button-row">
            <button type="submit">{{ $creating ? 'Create Plan' : 'Save Changes' }}</button>
            <a class="button secondary" href="{{ route($routePrefix.'membership-plans.index') }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
