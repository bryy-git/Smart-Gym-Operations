@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@php($isEditing = $equipment !== null)

@section('title', $isEditing ? 'Edit Equipment' : 'Add Equipment')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'equipment.index') }}">Equipment</a> / {{ $isEditing ? 'Edit' : 'Add' }}</p>
<h1>{{ $isEditing ? 'Edit Equipment' : 'Add Equipment' }}</h1>
<section class="card">
    <form method="POST" action="{{ $isEditing ? route($routePrefix.'equipment.update', $equipment) : route($routePrefix.'equipment.store') }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif
        <div class="field">
            <label for="equipment_name">Equipment name</label>
            <input id="equipment_name" name="equipment_name" maxlength="150" value="{{ old('equipment_name', $equipment?->equipment_name) }}" required>
        </div>
        <div class="field">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description" rows="3" maxlength="10000">{{ old('description', $equipment?->description) }}</textarea>
        </div>
        <div class="field">
            <label for="equipment_status">Status</label>
            <select id="equipment_status" name="equipment_status" required>
                @foreach (['Available', 'Under Maintenance', 'Unavailable'] as $option)
                    <option value="{{ $option }}" @selected(old('equipment_status', $equipment?->equipment_status ?? 'Available') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="usage_threshold">Usage threshold (optional)</label>
            <input id="usage_threshold" name="usage_threshold" type="number" min="1" max="4294967295" step="1" value="{{ old('usage_threshold', $equipment?->usage_threshold) }}">
            <small>Alerts compare usage accumulated since the latest resolved maintenance.</small>
        </div>
        <div class="button-row">
            <button type="submit">{{ $isEditing ? 'Save changes' : 'Add equipment' }}</button>
            <a class="button secondary" href="{{ $isEditing ? route($routePrefix.'equipment.show', $equipment) : route($routePrefix.'equipment.index') }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
