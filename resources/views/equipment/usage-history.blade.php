@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Equipment Usage')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'equipment.show', $equipment) }}">{{ $equipment->equipment_name }}</a> / Usage history</p>
<h1>Usage history: {{ $equipment->equipment_name }}</h1>
<section class="card">
    <h2>Record usage</h2>
    <form method="POST" action="{{ route($routePrefix.'equipment.usage.store', $equipment) }}">
        @csrf
        <div class="field">
            <label for="usage_count">Usage count</label>
            <input id="usage_count" name="usage_count" type="number" min="0" max="4294967295" step="1" value="{{ old('usage_count', 0) }}" required>
        </div>
        <div class="field">
            <label for="usage_date">Usage date</label>
            <input id="usage_date" name="usage_date" type="date" max="{{ today()->toDateString() }}" value="{{ old('usage_date', today()->toDateString()) }}" required>
        </div>
        <div class="field">
            <label for="notes">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" maxlength="10000">{{ old('notes') }}</textarea>
        </div>
        <button type="submit">Record usage</button>
    </form>
</section>

<section class="card">
    <h2>Recorded usage</h2>
    @if ($records->isEmpty())
        <p>No usage records have been recorded.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Date</th><th>Usage count</th><th>Recorded by</th><th>Notes</th></tr></thead>
                <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td>{{ $record->usage_date->format('M j, Y') }}</td>
                        <td>{{ $record->usage_count }}</td>
                        <td>{{ $record->recorder->first_name }} {{ $record->recorder->last_name }}</td>
                        <td>{{ $record->notes ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $records->links() }}</div>
    @endif
</section>
@endsection
