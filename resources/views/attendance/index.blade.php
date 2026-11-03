@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Attendance')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Attendance</p>
<div class="page-heading">
    <div>
        <h1>Attendance</h1>
        <p>Review check-ins, open sessions, corrections, and current gym occupancy.</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'attendance.check-in.create') }}">Staff-assisted check-in</a>
</div>

<section class="card">
    <h2>Current occupancy</h2>
    <p id="occupancy-value" data-occupancy-endpoint="{{ route($routePrefix.'attendance.occupancy') }}">
        {{ $occupancy['occupancy'] }} / {{ $occupancy['maximum_capacity'] ?? 'capacity not configured' }}
    </p>
    <p id="occupancy-warning" class="warning" role="status" @if (! $occupancy['at_capacity']) hidden @endif>
        The gym has reached or exceeded its configured capacity. Valid entries are still allowed.
    </p>
    <p id="occupancy-error" class="error" role="alert" hidden>Occupancy could not be refreshed. Retrying shortly.</p>
</section>

@if ($routePrefix === 'admin.')
    <section class="card">
        <h2>Maximum capacity</h2>
        <form method="POST" action="{{ route('admin.attendance.capacity.update') }}">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="maximum_capacity">Maximum capacity</label>
                <input id="maximum_capacity" name="maximum_capacity" type="number" min="1" max="1000000" step="1" value="{{ old('maximum_capacity', $maximumCapacity) }}" required>
            </div>
            <button type="submit">Save capacity</button>
        </form>
    </section>
@endif

<section class="card">
    <form class="filters" method="GET" action="{{ route($routePrefix.'attendance.index') }}">
        <label for="search">Member search</label>
        <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or email">
        <label for="date_from">Check-in from</label>
        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
        <label for="date_to">Check-in to</label>
        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
        <label for="status">Session</label>
        <select id="status" name="status">
            <option value="">All</option>
            <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
            <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
        </select>
        <label for="source">Source</label>
        <select id="source" name="source">
            <option value="">All</option>
            @foreach (['Staff-assisted', 'Member self-service'] as $option)
                <option value="{{ $option }}" @selected(($filters['source'] ?? '') === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit">Apply filters</button>
        @if (count(array_filter($filters)) > 0)
            <a class="button secondary" href="{{ route($routePrefix.'attendance.index') }}">Clear filters</a>
        @endif
    </form>
</section>

<section class="card">
    @if ($attendances->isEmpty())
        <p>No attendance records match these filters.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Member</th><th>Check-in</th><th>Check-out</th><th>Source</th><th>Session</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->member->first_name }} {{ $attendance->member->last_name }}<br>{{ $attendance->member->user->email }}</td>
                        <td>{{ $attendance->check_in_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $attendance->check_out_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td>{{ $attendance->attendance_source }}</td>
                        <td>{{ $attendance->check_out_at === null ? 'Open' : 'Closed' }}</td>
                        <td>
                            @if ($attendance->check_out_at === null)
                                <form method="POST" action="{{ route($routePrefix.'attendance.check-out', $attendance) }}">
                                    @csrf
                                    <button type="submit">Check out</button>
                                </form>
                            @endif
                            <a href="{{ route($routePrefix.'attendance.edit', $attendance) }}">Correct</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $attendances->links() }}</div>
    @endif
</section>

<script>
    (() => {
        const occupancyElement = document.getElementById('occupancy-value');
        const warningElement = document.getElementById('occupancy-warning');
        const errorElement = document.getElementById('occupancy-error');
        const endpoint = occupancyElement.dataset.occupancyEndpoint;

        async function refreshOccupancy() {
            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) {
                    throw new Error(`Occupancy request failed with status ${response.status}`);
                }

                const occupancy = await response.json();
                const capacity = occupancy.maximum_capacity ?? 'capacity not configured';
                occupancyElement.textContent = `${occupancy.occupancy} / ${capacity}`;
                warningElement.hidden = !occupancy.at_capacity;
                warningElement.textContent = occupancy.occupancy > occupancy.maximum_capacity
                    ? 'The gym is over its configured capacity. Valid entries are still allowed.'
                    : 'The gym has reached its configured capacity. Valid entries are still allowed.';
                errorElement.hidden = true;
            } catch (error) {
                console.error(error);
                errorElement.hidden = false;
            }
        }

        refreshOccupancy();
        window.setInterval(refreshOccupancy, 5000);
    })();
</script>
@endsection
