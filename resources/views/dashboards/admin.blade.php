@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    @php
        $hour = $attendance['asOf']->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $maxCheckIns = max(array_column($attendanceChart, 'value'));
        $maxMonthlyPayments = max(array_column($revenueChart, 'value'));
        $attendanceMax = max(1, $maxCheckIns);
        $revenueMax = max(1, $maxMonthlyPayments);
        $attendanceChartStep = 700 / max(count($attendanceChart) - 1, 1);
        $revenueChartStep = 700 / max(count($revenueChart) - 1, 1);
        $peakAttendance = collect($attendanceChart)->sortByDesc('value')->first();

        $kpis = [
            ['label' => 'Total members', 'value' => $totalMembers, 'description' => 'Registered member profiles', 'route' => route('admin.members.index'), 'icon' => 'members'],
            ['label' => 'Active memberships', 'value' => $activeMemberships, 'description' => 'Valid today', 'route' => route('admin.members.index'), 'icon' => 'membership'],
            ['label' => 'Check-ins today', 'value' => $attendance['checkInsToday'], 'description' => 'Recorded today', 'route' => route('admin.attendance.index'), 'icon' => 'checkin'],
            ['label' => 'Currently in gym', 'value' => $attendance['currentlyInGym'], 'description' => 'Open attendance sessions', 'route' => route('admin.attendance.index'), 'icon' => 'occupancy'],
        ];
    @endphp

    <header class="page-heading admin-page-heading">
        <div>
            <p class="admin-dashboard-eyebrow">{{ $attendance['asOf']->format('l, F j, Y') }}</p>
            <h1>{{ $greeting }}, {{ auth()->user()->first_name ?: 'Admin' }}.</h1>
            <p>Here's what's happening in your gym today.</p>
        </div>
        <div class="admin-dashboard-utilities">
            <a class="admin-icon-link" href="{{ route('admin.notifications.index') }}" aria-label="Notifications, {{ $unreadNotificationCount }} unread">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path d="M10 21h4" />
                </svg>
                <span>Notifications</span>
                @if ($unreadNotificationCount > 0)
                    <span class="admin-notification-count">{{ $unreadNotificationCount }}</span>
                @endif
            </a>
        </div>
    </header>

    <section class="admin-kpi-grid" aria-label="Gym overview">
        @foreach ($kpis as $kpi)
            @if ($kpi['route'])
                <a class="admin-kpi-card" href="{{ $kpi['route'] }}">
            @else
                <article class="admin-kpi-card">
            @endif
                <span class="admin-kpi-icon" aria-hidden="true">
                    @switch($kpi['icon'])
                        @case('members')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                            @break
                        @case('active')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="m8 12 2.5 2.5L16 9" /></svg>
                            @break
                        @case('membership')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z" /><path d="M4 5.5v13A2.5 2.5 0 0 1 6.5 16H20" /></svg>
                            @break
                        @case('checkin')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="m10 17 5-5-5-5M15 12H3" /></svg>
                            @break
                        @case('occupancy')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4" /><path d="M5 21a7 7 0 0 1 14 0" /></svg>
                            @break
                        @case('payment')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h3" /></svg>
                            @break
                        @case('expiry')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18M12 14v3M12 14h2" /></svg>
                            @break
                        @case('equipment')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v16M18 4v16M3 8h6M15 8h6M3 16h6M15 16h6" /></svg>
                            @break
                    @endswitch
                </span>
                <span class="admin-kpi-content">
                    <span class="admin-kpi-label">{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                    <span class="admin-kpi-description">{{ $kpi['description'] }}</span>
                </span>
@if ($kpi['route'])
                </a>
            @else
                </article>
            @endif
        @endforeach
    </section>

    <section id="admin-analytics" class="admin-section" aria-labelledby="admin-analytics-title">
        <div class="admin-section-heading">
            <div>
                <h2 id="admin-analytics-title">Analytics</h2>
                <p>Attendance and confirmed payment records over time.</p>
            </div>
        </div>
        <div class="admin-chart-grid">
            <figure class="admin-chart-panel">
                <figcaption class="admin-chart-heading">
                    <div>
                        <span class="admin-chart-kicker">Gym activity</span>
                        <h3>Attendance trends</h3>
                    </div>
                    <span class="admin-chart-stat">Peak: <strong>{{ $peakAttendance['value'] ?? 0 }}</strong> check-ins &middot; {{ $peakAttendance['period'] ?? 'No data' }}</span>
                </figcaption>
                <div class="admin-line-chart" role="img" aria-label="Daily check-ins during the last 7 days">
                    <svg viewBox="0 0 700 250" preserveAspectRatio="none" aria-hidden="true">
                        <defs><linearGradient id="attendanceFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#73a7ff" stop-opacity=".32"/><stop offset="1" stop-color="#73a7ff" stop-opacity="0"/></linearGradient></defs>
                        @for ($grid = 1; $grid <= 4; $grid++) <line class="admin-chart-gridline" x1="0" x2="700" y1="{{ 20 + ($grid * 42) }}" y2="{{ 20 + ($grid * 42) }}"/> @endfor
                        <polygon class="admin-chart-area" points="0,230 @foreach ($attendanceChart as $index => $day) {{ $index * $attendanceChartStep }},{{ 220 - (($day['value'] / $attendanceMax) * 180) }} @endforeach 700,230"/>
                        <polyline class="admin-chart-line" points="@foreach ($attendanceChart as $index => $day) {{ $index * $attendanceChartStep }},{{ 220 - (($day['value'] / $attendanceMax) * 180) }} @endforeach"/>
                        @foreach ($attendanceChart as $index => $day)<circle class="admin-chart-dot" cx="{{ $index * $attendanceChartStep }}" cy="{{ 220 - (($day['value'] / $attendanceMax) * 180) }}" r="5"><title>{{ $day['period'] }}: {{ $day['value'] }} check-ins</title></circle>@endforeach
                    </svg>
                    <div class="admin-line-labels" style="--chart-label-count: {{ count($attendanceChart) }};">@foreach ($attendanceChart as $day)<span>{{ $day['label'] }}</span>@endforeach</div>
                </div>
                @if ($maxCheckIns === 0)
                    <p class="admin-chart-empty">No check-ins recorded in this period.</p>
                @endif
            </figure>

            <figure class="admin-chart-panel">
                <figcaption class="admin-chart-heading">
                    <div>
                        <span class="admin-chart-kicker">Revenue</span>
                        <h3>Revenue overview <span class="admin-currency">(PHP &middot; &#8369;)</span></h3>
                    </div>
                    <form class="admin-chart-filter" method="get" data-dashboard-filter>
                        <label for="revenue-range">Revenue period</label>
                        <select id="revenue-range" name="revenue_range" aria-label="Revenue chart range">
                            <option value="6" @selected($revenueMonths === 6)>6 months</option>
                            <option value="12" @selected($revenueMonths === 12)>12 months</option>
                        </select>
                    </form>
                </figcaption>
                <div class="admin-line-chart" role="img" aria-label="Payments received each month during the last 6 months, in Philippine pesos">
                    <svg viewBox="0 0 700 250" preserveAspectRatio="none" aria-hidden="true">
                        <defs><linearGradient id="revenueFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#70d6b2" stop-opacity=".3"/><stop offset="1" stop-color="#70d6b2" stop-opacity="0"/></linearGradient></defs>
                        @for ($grid = 1; $grid <= 4; $grid++) <line class="admin-chart-gridline" x1="0" x2="700" y1="{{ 20 + ($grid * 42) }}" y2="{{ 20 + ($grid * 42) }}"/> @endfor
                        <polygon class="admin-chart-area admin-chart-area--green" points="0,230 @foreach ($revenueChart as $index => $month) {{ $index * $revenueChartStep }},{{ 220 - (($month['value'] / $revenueMax) * 180) }} @endforeach 700,230"/>
                        <polyline class="admin-chart-line admin-chart-line--green" points="@foreach ($revenueChart as $index => $month) {{ $index * $revenueChartStep }},{{ 220 - (($month['value'] / $revenueMax) * 180) }} @endforeach"/>
                        @foreach ($revenueChart as $index => $month)<circle class="admin-chart-dot admin-chart-dot--green" cx="{{ $index * $revenueChartStep }}" cy="{{ 220 - (($month['value'] / $revenueMax) * 180) }}" r="5"><title>{{ $month['period'] }}: ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â±{{ number_format($month['value'], 2) }}</title></circle>@endforeach
                    </svg>
                    <div class="admin-line-labels" style="--chart-label-count: {{ count($revenueChart) }};">@foreach ($revenueChart as $month)<span>{{ $month['label'] }}</span>@endforeach</div>
                </div>
                @if ($maxMonthlyPayments <= 0)
                    <p class="admin-chart-empty">No payment records in this period.</p>
                @endif
                <p class="admin-chart-footnote">Recorded payments are treated as received; this schema does not store a payment confirmation status.</p>
            </figure>
        </div>
    </section>

    <section class="admin-revenue-summary" aria-labelledby="admin-revenue-title">
        <div class="admin-revenue-header">
            <div>
                <span class="admin-chart-kicker">Finance</span>
                <h2 id="admin-revenue-title">Revenue this month</h2>
            </div>
            <span class="admin-revenue-month">{{ $attendance['asOf']->format('F Y') }}</span>
        </div>
        <div class="admin-revenue-metrics">
            <div>
                <span>Payments received</span>
                <strong>&#8369;{{ number_format((float) $grossRevenue, 2) }}</strong>
            </div>
            <div>
                <span>Approved refunds</span>
                <strong>&#8369;{{ number_format((float) $approvedRefunds, 2) }}</strong>
            </div>
            <div class="admin-net-revenue">
                <span>Net revenue</span>
                <strong>&#8369;{{ number_format((float) $netRevenue, 2) }}</strong>
            </div>
        </div>
        <div class="admin-revenue-links">
            <a href="{{ route('admin.payments.index') }}">View payments <span aria-hidden="true">&rarr;</span></a>
            <a href="{{ route('admin.refunds.index') }}">View refunds <span aria-hidden="true">&rarr;</span></a>
        </div>
    </section>

    <section class="admin-section" aria-label="Operations and shortcuts">
        <div class="admin-operations-grid">
            <div class="admin-surface admin-attention-panel">
                <div class="admin-section-heading">
                    <div>
                        <h2>Needs attention</h2>
                        <p>Items that may need follow-up.</p>
                    </div>
                </div>
                <ul class="admin-attention-list">
                    <li class="{{ $expiringMembershipCount > 0 ? 'is-active' : 'is-clear' }}">
                        <span class="admin-status-mark" aria-hidden="true"></span>
                        <span class="admin-attention-copy">
                            <strong>Expiring memberships</strong>
                            <span>Within the next 7 days</span>
                        </span>
                        <span class="admin-attention-count">{{ $expiringMembershipCount }}</span>
                        <a class="admin-row-link" href="{{ route('admin.memberships.index') }}" aria-label="View expiring memberships">View</a>
                    </li>
                    <li class="{{ $pendingApplicationCount > 0 ? 'is-active' : 'is-clear' }}">
                        <span class="admin-status-mark" aria-hidden="true"></span>
                        <span class="admin-attention-copy">
                            <strong>Pending online applications</strong>
                            <span>Awaiting review</span>
                        </span>
                        <span class="admin-attention-count">{{ $pendingApplicationCount }}</span>
                        <span class="admin-unavailable-label">Review page unavailable</span>
                    </li>
                    <li class="{{ $equipmentAlertCount > 0 ? 'is-active' : 'is-clear' }}">
                        <span class="admin-status-mark" aria-hidden="true"></span>
                        <span class="admin-attention-copy">
                            <strong>Equipment maintenance</strong>
                            <span>Maintenance or usage alerts</span>
                        </span>
                        <span class="admin-attention-count">{{ $equipmentAlertCount }}</span>
                        <a class="admin-row-link" href="{{ route('admin.equipment.index') }}" aria-label="View equipment alerts">View</a>
                    </li>
                    <li class="{{ $inactiveMemberCount > 0 ? 'is-active' : 'is-clear' }}">
                        <span class="admin-status-mark" aria-hidden="true"></span>
                        <span class="admin-attention-copy">
                            <strong>Inactive members</strong>
                            <span>Members marked inactive</span>
                        </span>
                        <span class="admin-attention-count">{{ $inactiveMemberCount }}</span>
                        <a class="admin-row-link" href="{{ route('admin.members.index') }}" aria-label="View inactive members">View</a>
                    </li>
                </ul>
                @if ($expiringMemberships->isNotEmpty())
                    <details class="admin-attention-details">
                        <summary>View upcoming expirations</summary>
                        <ul>
                            @foreach ($expiringMemberships as $membership)
                                <li>
                                    {{ $membership->member?->first_name }} {{ $membership->member?->last_name }}
                                    <span>{{ $membership->plan_name_snapshot }} &middot; {{ $membership->end_date->format('M j, Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($inactiveMembers->isNotEmpty())
                    <details class="admin-attention-details">
                        <summary>View inactive members</summary>
                        <ul>
                            @foreach ($inactiveMembers as $member)
                                <li>{{ $member->first_name }} {{ $member->last_name }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($equipmentAlerts->isNotEmpty())
                    <details class="admin-attention-details">
                        <summary>View equipment alert details</summary>
                        <ul>
                            @foreach ($equipmentAlerts as $equipment)
                                <li>
                                    <strong>{{ $equipment->equipment_name }}</strong>
                                    <span>{{ $equipment->equipment_status }}
                                        @if ($equipment->maintenanceRecords->isNotEmpty())
                                            &middot; {{ $equipment->maintenanceRecords->first()->maintenance_status }}
                                        @endif
                                        @if ($equipment->usage_threshold !== null && $equipment->usage_since_maintenance >= $equipment->usage_threshold)
                                            &middot; usage {{ $equipment->usage_since_maintenance }} / threshold {{ $equipment->usage_threshold }}
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>

            <div class="admin-surface admin-quick-actions">
                <div class="admin-section-heading">
                    <div>
                        <h2>Quick actions</h2>
                        <p>Common tasks, one click away.</p>
                    </div>
                </div>
                <div class="admin-action-grid">
                    <a href="{{ route('admin.members.create') }}"><span aria-hidden="true">&#65291;</span> Add member</a>
                    <a href="{{ route('admin.payments.create') }}"><span aria-hidden="true">&#65291;</span> Record payment</a>
                    <a href="{{ route('admin.attendance.index') }}"><span aria-hidden="true">&#8599;</span> View attendance</a>
                    <a href="{{ route('admin.equipment.create') }}"><span aria-hidden="true">&#65291;</span> Add equipment</a>
                    <a href="{{ route('admin.membership-plans.index') }}"><span aria-hidden="true">&#8599;</span> Manage plans</a>
                </div>
                <p class="admin-quick-action-note">Online application review is not currently available in the Admin routes.</p>
            </div>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="admin-activities-title">
        <div class="admin-surface admin-activities-panel">
            <div class="admin-section-heading">
                <div>
                    <h2 id="admin-activities-title">Recent activities</h2>
                    <p>Latest actions recorded by the system.</p>
                </div>
            </div>
            @if ($activities->isEmpty())
                <p class="admin-empty-state">No activities have been recorded yet.</p>
            @else
                <ol class="admin-activity-list">
                    @foreach ($activities as $activity)
                        <li>
                            <span class="admin-activity-marker" aria-hidden="true"></span>
                            <div class="admin-activity-content">
                                <p>{{ $activity->description ?: $activity->action }}</p>
                                <span>
                                    {{ $activity->user
                                        ? trim($activity->user->first_name.' '.$activity->user->last_name)
                                        : 'System' }}
                                    &middot; {{ $activity->affected_entity }}
                                    @if ($activity->affected_record_id)
                                        #{{ $activity->affected_record_id }}
                                    @endif
                                </span>
                            </div>
                            <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->format('M j, Y, g:i A') }}">
                                {{ $activity->created_at->diffForHumans() }}
                            </time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>
@endsection




