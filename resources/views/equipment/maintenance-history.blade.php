@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Maintenance History')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'equipment.show', $equipment) }}">{{ $equipment->equipment_name }}</a> / Maintenance history</p>
<h1>Maintenance history: {{ $equipment->equipment_name }}</h1>
<section class="card">
    @if ($records->isEmpty())
        <p>No maintenance records have been recorded.</p>
    @else
        @foreach ($records as $record)
            <article class="card">
                <h2>#{{ $record->maintenance_id }} — {{ $record->maintenance_status }}</h2>
                <p><strong>Reported:</strong> {{ $record->reported_at->format('M j, Y g:i A') }} by {{ $record->reporter->first_name }} {{ $record->reporter->last_name }}</p>
                <p><strong>Issue:</strong> {{ $record->issue_description }}</p>
                <p><strong>Action details:</strong> {{ $record->action_details ?? '—' }}</p>
                <p><strong>Resolved:</strong> {{ $record->resolved_at?->format('M j, Y g:i A') ?? 'Not resolved' }}</p>
                <form method="POST" action="{{ route($routePrefix.'equipment.maintenance.update', [$equipment, $record]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="field">
                        <label for="maintenance_status_{{ $record->maintenance_id }}">Maintenance status</label>
                        <select id="maintenance_status_{{ $record->maintenance_id }}" name="maintenance_status" required>
                            @foreach (['Reported', 'In Progress', 'Resolved'] as $option)
                                <option value="{{ $option }}" @selected($record->maintenance_status === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="action_details_{{ $record->maintenance_id }}">Action details</label>
                        <textarea id="action_details_{{ $record->maintenance_id }}" name="action_details" rows="2" maxlength="10000">{{ $record->action_details }}</textarea>
                    </div>
                    <button type="submit">Update maintenance record</button>
                </form>
            </article>
        @endforeach
        <div class="pagination">{{ $records->links() }}</div>
    @endif
</section>
@endsection
