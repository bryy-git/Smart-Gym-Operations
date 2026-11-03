@extends('layouts.admin')

@php
    $creating = $closure === null;
    $pageTitle = $creating ? 'Announce a Closure' : 'Edit Closure Announcement';
    $action = $creating
        ? route('admin.gym-closures.store')
        : route('admin.gym-closures.update', $closure);
@endphp

@section('title', $pageTitle)

@section('content')
<p class="breadcrumb"><a href="{{ route('admin.gym-closures.index') }}">Gym Closures</a> / {{ $pageTitle }}</p>
<h1>{{ $pageTitle }}</h1>
<section class="card">
    <form method="POST" action="{{ $action }}">
        @csrf
        @unless ($creating)
            @method('PUT')
        @endunless
        <div class="field">
            <label for="title">Announcement title</label>
            <input id="title" name="title" value="{{ old('title', $closure?->title ?? '') }}" maxlength="180" required>
        </div>
        <div class="field">
            <label for="reason">Details (optional)</label>
            <textarea id="reason" name="reason" rows="5" maxlength="10000">{{ old('reason', $closure?->reason ?? '') }}</textarea>
        </div>
        <div class="field">
            <label for="start_datetime">Starts</label>
            <input id="start_datetime" name="start_datetime" type="datetime-local" value="{{ old('start_datetime', $closure?->start_datetime?->format('Y-m-d\TH:i') ?? '') }}" required>
        </div>
        <div class="field">
            <label for="end_datetime">Ends</label>
            <input id="end_datetime" name="end_datetime" type="datetime-local" value="{{ old('end_datetime', $closure?->end_datetime?->format('Y-m-d\TH:i') ?? '') }}" required>
        </div>
        <p class="muted">Status is derived from the announcement dates and updates automatically.</p>
        <div class="button-row">
            <button type="submit">{{ $creating ? 'Publish closure announcement' : 'Save changes' }}</button>
            <a class="button secondary" href="{{ route('admin.gym-closures.index') }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
