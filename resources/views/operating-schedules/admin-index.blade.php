@extends('layouts.admin')

@section('title', 'Operating Schedule')

@section('content')
<p class="breadcrumb">Admin / Operating Schedule</p>
<div class="page-heading">
    <div>
        <h1>Operating Schedule</h1>
        <p class="muted">Set the gym's weekly opening hours. Intervals on the same day cannot overlap.</p>
    </div>
    <a class="button" href="{{ route('admin.gym-closures.index') }}">Manage closures</a>
</div>

@foreach ($dayNames as $day => $dayName)
    @php($daySchedules = $schedules->get($day, collect()))
    <section class="card">
        <div class="page-heading">
            <h2>{{ $dayName }}</h2>
            @if ($daySchedules->contains(fn ($schedule) => $schedule->is_open))
                <form method="POST" action="{{ route('admin.operating-schedules.day.close', $day) }}">
                    @csrf
                    @method('PATCH')
                    <button class="danger" type="submit">Close {{ $dayName }}</button>
                </form>
            @endif
        </div>

        @if ($daySchedules->isEmpty() || $daySchedules->every(fn ($schedule) => ! $schedule->is_open))
            <p class="muted">Closed</p>
        @endif

        @foreach ($daySchedules as $schedule)
            <form class="filters" method="POST" action="{{ route('admin.operating-schedules.update', $schedule) }}">
                @csrf
                @method('PUT')
                <label for="opening_time_{{ $schedule->schedule_id }}">Opens</label>
                <input id="opening_time_{{ $schedule->schedule_id }}" name="opening_time" type="time" value="{{ substr($schedule->opening_time, 0, 5) }}" required>
                <label for="closing_time_{{ $schedule->schedule_id }}">Closes</label>
                <input id="closing_time_{{ $schedule->schedule_id }}" name="closing_time" type="time" value="{{ substr($schedule->closing_time, 0, 5) }}" required>
                <label for="is_open_{{ $schedule->schedule_id }}">Status</label>
                <select id="is_open_{{ $schedule->schedule_id }}" name="is_open">
                    <option value="1" @selected($schedule->is_open)>Open</option>
                    <option value="0" @selected(! $schedule->is_open)>Closed</option>
                </select>
                <button type="submit">Save interval</button>
            </form>
        @endforeach

        <form class="filters" method="POST" action="{{ route('admin.operating-schedules.store') }}">
            @csrf
            <input type="hidden" name="day_of_week" value="{{ $day }}">
            <label for="new_opening_{{ $day }}">Add interval: opens</label>
            <input id="new_opening_{{ $day }}" name="opening_time" type="time" required>
            <label for="new_closing_{{ $day }}">Closes</label>
            <input id="new_closing_{{ $day }}" name="closing_time" type="time" required>
            <button type="submit">Add interval</button>
        </form>
    </section>
@endforeach
@endsection
