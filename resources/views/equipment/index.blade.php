@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Equipment')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Equipment</p>
<div class="page-heading">
    <div>
        <h1>Equipment</h1>
        <p>{{ $alertCount }} equipment item(s) currently require attention.</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'equipment.create') }}">Add equipment</a>
</div>

<section class="card">
    <form class="filters" method="GET" action="{{ route($routePrefix.'equipment.index') }}">
        <label for="search">Name</label>
        <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Equipment name">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            @foreach (['Available', 'Under Maintenance', 'Unavailable'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit">Apply filters</button>
        @if ($search !== '' || $status !== '')
            <a class="button secondary" href="{{ route($routePrefix.'equipment.index') }}">Clear filters</a>
        @endif
    </form>
</section>

<section class="card">
    @if ($equipment->isEmpty())
        <p>No equipment matches these filters.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Equipment</th><th>Status</th><th>Usage since resolved maintenance</th><th>Threshold</th><th>Attention</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($equipment as $item)
                    @php
                        $thresholdReached = $item->usage_threshold !== null
                            && (int) $item->usage_since_maintenance >= $item->usage_threshold;
                        $hasOpenMaintenance = $item->maintenanceRecords->isNotEmpty();
                        $needsAttention = $item->equipment_status !== 'Available' || $hasOpenMaintenance || $thresholdReached;
                    @endphp
                    <tr>
                        <td>{{ $item->equipment_name }}</td>
                        <td>{{ $item->equipment_status }}</td>
                        <td>{{ $item->usage_since_maintenance }}</td>
                        <td>{{ $item->usage_threshold ?? 'Not set' }}</td>
                        <td>{{ $needsAttention ? 'Attention required' : 'No alert' }}</td>
                        <td><a href="{{ route($routePrefix.'equipment.show', $item) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $equipment->links() }}</div>
    @endif
</section>
@endsection
