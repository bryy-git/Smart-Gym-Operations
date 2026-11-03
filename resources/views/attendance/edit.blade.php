@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Correct Attendance')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'attendance.index') }}">Attendance</a> / Correct attendance</p>
<h1>Correct attendance #{{ $attendance->attendance_id }}</h1>
<section class="card">
    <p>{{ $attendance->member->first_name }} {{ $attendance->member->last_name }} — {{ $attendance->member->user->email }}</p>
    <form method="POST" action="{{ route($routePrefix.'attendance.update', $attendance) }}">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="check_in_at">Check-in time</label>
            <input id="check_in_at" name="check_in_at" type="datetime-local" step="1" value="{{ old('check_in_at', $attendance->check_in_at->format('Y-m-d\TH:i:s')) }}" required>
        </div>
        <div class="field">
            <label for="check_out_at">Check-out time (leave blank for an open session)</label>
            <input id="check_out_at" name="check_out_at" type="datetime-local" step="1" value="{{ old('check_out_at', $attendance->check_out_at?->format('Y-m-d\TH:i:s')) }}">
        </div>
        <div class="field">
            <label for="correction_reason">Correction reason</label>
            <textarea id="correction_reason" name="correction_reason" rows="4" maxlength="5000" required>{{ old('correction_reason') }}</textarea>
        </div>
        <div class="button-row">
            <button type="submit">Save correction</button>
            <a class="button secondary" href="{{ route($routePrefix.'attendance.index') }}">Cancel</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Correction history</h2>
    @if ($attendance->corrections->isEmpty())
        <p>No corrections have been made.</p>
    @else
        @foreach ($attendance->corrections as $correction)
            <article>
                <h3>{{ $correction->corrected_at->format('M j, Y g:i A') }} by {{ $correction->corrector->first_name }} {{ $correction->corrector->last_name }}</h3>
                <p>Check-in: {{ $correction->original_check_in_at->format('M j, Y g:i A') }} → {{ $correction->corrected_check_in_at->format('M j, Y g:i A') }}</p>
                <p>Check-out: {{ $correction->original_check_out_at?->format('M j, Y g:i A') ?? 'Open' }} → {{ $correction->corrected_check_out_at?->format('M j, Y g:i A') ?? 'Open' }}</p>
                <p>Reason: {{ $correction->correction_reason }}</p>
            </article>
        @endforeach
    @endif
</section>
@endsection
