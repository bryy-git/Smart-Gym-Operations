@extends('layouts.account')

@section('title', 'Member Dashboard')

@section('content')
    <header class="member-dashboard-heading">
        <div>
            <p class="member-dashboard-eyebrow">{{ now()->format('l, F j, Y') }}</p>
            <h1>Welcome back, {{ $member?->first_name ?: auth()->user()->first_name ?: 'Member' }}.</h1>
            <p>Here's your membership overview.</p>
        </div>
    </header>

    @if (! $member)
        <div class="error" role="status">No member profile is linked to this account yet. Contact gym staff for assistance.</div>
    @else
        <section class="member-membership-hero" aria-label="Current membership">
            <div>
                <p class="member-card-kicker">Current membership</p>
                <h2>{{ $membership?->plan_name_snapshot ?? 'No active plan' }}</h2>
                <p class="member-membership-meta">
                    <span class="member-status-badge">{{ $membershipStatus }}</span>
                    <span>{{ $membership?->end_date?->format('M j, Y') ?? 'No expiration date' }}</span>
                </p>
            </div>
            <a class="button secondary" href="{{ route('member.memberships.index') }}">View membership</a>
        </section>

        <section class="member-stat-grid" aria-label="Your activity">
            <div class="member-stat-card"><span>Visits this month</span><strong>{{ $attendanceCount }}</strong></div>
            <div class="member-stat-card"><span>Last check-in</span><strong>{{ $recentAttendance->first()?->check_in_at?->format('M j, Y') ?? 'No visits yet' }}</strong></div>
            <div class="member-stat-card"><span>Member since</span><strong>{{ $member?->created_at?->format('M Y') ?? 'Not available' }}</strong></div>
        </section>
        <section class="member-attendance-heatmap" aria-labelledby="attendance-activity-title">
            <div class="member-heatmap-heading">
                <div>
                    <h2 id="attendance-activity-title">Attendance activity</h2>
                    <p>Your attendance over the selected year.</p>
                </div>
                <form class="member-heatmap-filter" method="get"><label for="attendance-year">Year</label><select id="attendance-year" name="attendance_year" onchange="this.form.submit()">@foreach (range(now()->year, now()->year - 5) as $year)<option value="{{ $year }}" @selected($attendanceYear === $year)>{{ $year }}</option>@endforeach</select></form><div class="member-heatmap-legend" aria-label="Attendance intensity"><span>Less</span><i class="heat-level-0"></i><i class="heat-level-1"></i><i class="heat-level-2"></i><i class="heat-level-3"></i><i class="heat-level-4"></i><span>More</span></div>
            </div>
            <div class="member-heatmap-months" aria-hidden="true">@foreach ($attendanceMonthLabels as $month)<span style="grid-column: {{ $month['week'] }} / span 4;">{{ $month['label'] }}</span>@endforeach</div>
            <div class="member-heatmap-grid" role="list" aria-label="Daily attendance">
                @foreach ($attendanceHeatmap as $day)
                    <span class="member-heat-cell heat-level-{{ $day['level'] }}{{ $day['outside'] ? ' heat-outside' : '' }}" role="listitem" @if (! $day['outside']) data-tooltip="{{ $day['date']->format('M j, Y') }}: {{ $day['count'] > 0 ? $day['count'] . ' attendance - ' . number_format($day['hours'], 1) . ' hours' : 'No attendance' }}" aria-label="{{ $day['date']->format('M j, Y') }}" @endif></span>
                @endforeach
            </div>
        </section>
    @endif
@endsection


