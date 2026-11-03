@extends('layouts.staff')

@section('title', 'Staff Dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Staff Dashboard</h1>
            <p class="muted">Today's attendance and operational work.</p>
        </div>
    </div>

    <div class="dashboard-cards">
        <section class="card dashboard-card">
            <h2>Check-ins today</h2>
            <strong>{{ $attendance['checkInsToday'] }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Currently in gym</h2>
            <strong>{{ $attendance['currentlyInGym'] }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Active members</h2>
            <strong>{{ $activeMembers }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Inactive members</h2>
            <strong>{{ $inactiveMembers }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Pending membership requests</h2>
            <strong>{{ $pendingRequestCount }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Equipment alerts</h2>
            <strong>{{ $equipmentAlertCount }}</strong>
        </section>
    </div>

    <section class="card">
        <h2>Gym closure announcements</h2>
        @if ($relevantClosures->isEmpty())
            <p class="muted">There are no upcoming or active closure announcements.</p>
        @else
            <ul>
                @foreach ($relevantClosures as $closure)
                    <li>
                        <strong>{{ $closure->title }}</strong>
                        — {{ $closure->start_datetime->format('M j, Y g:i A') }} to {{ $closure->end_datetime->format('M j, Y g:i A') }}
                        @if ($closure->reason)
                            — {{ $closure->reason }}
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        <p><a href="{{ route('staff.operating-schedules.index') }}">View weekly hours and all relevant closures</a></p>
    </section>

    <section class="card">
        <h2>Today's attendance</h2>
        @if ($recentAttendance->isEmpty())
            <p class="muted">No check-ins recorded today.</p>
        @else
            <ul>
                @foreach ($recentAttendance as $attendanceRecord)
                    <li>
                        {{ $attendanceRecord->member?->first_name }} {{ $attendanceRecord->member?->last_name }}
                        — {{ $attendanceRecord->check_in_at->format('g:i A') }}
                        @if ($attendanceRecord->check_out_at === null)
                            (currently in gym)
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card">
        <h2>Membership requests requiring attention</h2>
        @if ($pendingRequests->isEmpty())
            <p class="muted">There are no pending membership requests.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                    <tr><th>Member</th><th>Request</th><th>Submitted</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($pendingRequests as $request)
                        <tr>
                            <td>{{ $request->member?->first_name }} {{ $request->member?->last_name }}</td>
                            <td>{{ $request->request_type }}</td>
                            <td>{{ $request->requested_at->format('M j, Y g:i A') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card">
        <h2>Equipment alerts</h2>
        @if ($equipmentAlerts->isEmpty())
            <p class="muted">No equipment currently requires attention.</p>
        @else
            <ul>
                @foreach ($equipmentAlerts as $equipment)
                    <li>
                        <strong>{{ $equipment->equipment_name }}</strong> — {{ $equipment->equipment_status }}
                        @if ($equipment->usage_threshold !== null && $equipment->usage_since_maintenance >= $equipment->usage_threshold)
                            (usage {{ $equipment->usage_since_maintenance }} / threshold {{ $equipment->usage_threshold }})
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card">
        <h2>Recently added members</h2>
        @if ($recentMembers->isEmpty())
            <p class="muted">No member profiles are available.</p>
        @else
            <ul>
                @foreach ($recentMembers as $member)
                    <li>{{ $member->first_name }} {{ $member->last_name }} — {{ $member->member_status }}</li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
