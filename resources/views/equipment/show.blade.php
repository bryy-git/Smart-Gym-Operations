@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', $equipment->equipment_name)

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'equipment.index') }}">Equipment</a> / {{ $equipment->equipment_name }}</p>
<div class="page-heading">
    <h1>{{ $equipment->equipment_name }}</h1>
    <a class="button secondary" href="{{ route($routePrefix.'equipment.edit', $equipment) }}">Edit equipment</a>
</div>

<section class="card">
    <dl>
        <dt>Status</dt><dd>{{ $equipment->equipment_status }}</dd>
        <dt>Description</dt><dd>{{ $equipment->description ?? '—' }}</dd>
        <dt>Usage since last resolved maintenance</dt><dd>{{ $usageSinceMaintenance }}</dd>
        <dt>Usage threshold</dt><dd>{{ $equipment->usage_threshold ?? 'Not set' }}</dd>
    </dl>
</section>

<section class="card">
    <h2>Report equipment issue</h2>
    <form method="POST" action="{{ route($routePrefix.'equipment.maintenance.store', $equipment) }}">
        @csrf
        <div class="field">
            <label for="issue_description">Issue description</label>
            <textarea id="issue_description" name="issue_description" rows="3" maxlength="10000" required>{{ old('issue_description') }}</textarea>
        </div>
        <button type="submit">Report issue</button>
    </form>
</section>

<section class="card">
    <div class="page-heading">
        <h2>Open maintenance</h2>
        <a href="{{ route($routePrefix.'equipment.maintenance-history', $equipment) }}">Full maintenance history</a>
    </div>
    @if ($equipment->maintenanceRecords->isEmpty())
        <p>No unresolved maintenance records.</p>
    @else
        <ul>
            @foreach ($equipment->maintenanceRecords as $record)
                <li>#{{ $record->maintenance_id }} — {{ $record->maintenance_status }} — {{ $record->issue_description }}</li>
            @endforeach
        </ul>
    @endif
</section>

<section class="card">
    <div class="page-heading">
        <h2>Recent usage</h2>
        <a href="{{ route($routePrefix.'equipment.usage-history', $equipment) }}">Full usage history</a>
    </div>
    @if ($equipment->usageRecords->isEmpty())
        <p>No usage records have been recorded.</p>
    @else
        <ul>
            @foreach ($equipment->usageRecords as $usage)
                <li>{{ $usage->usage_date->format('M j, Y') }} — {{ $usage->usage_count }} — {{ $usage->recorder->first_name }} {{ $usage->recorder->last_name }}</li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
