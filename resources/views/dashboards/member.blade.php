@extends('layouts.account')

@section('title', 'Member Dashboard')

@section('content')
    <h1>Member Dashboard</h1>
    @if (! $member)
        <div class="error" role="status">No member profile is linked to this account yet. Contact gym staff for assistance.</div>
    @else
        <p>
            <a class="button" href="{{ route('member.plans.index') }}">Browse Plans</a>
            <a class="button" href="{{ route('member.memberships.index') }}">Membership History</a>
            <a class="button" href="{{ route('member.membership-requests.index') }}">My Requests</a>
            <a class="button" href="{{ route('member.attendance.index') }}">My Attendance</a>
            <a class="button" href="{{ route('member.operating-schedules.index') }}">Hours & Closures</a>
        </p>
    @endif

    <div class="dashboard-cards">
        <section class="card dashboard-card">
            <h2>Membership status</h2>
            <strong>{{ $membershipStatus }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Membership plan</h2>
            <strong>{{ $membership?->plan_name_snapshot ?? 'None' }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Membership expiration</h2>
            <strong>{{ $membership?->end_date?->format('M j, Y') ?? 'Not available' }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Visits in the last 30 days</h2>
            <strong>{{ $attendanceCount }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Total recorded payments</h2>
            <strong>{{ number_format((float) $paymentTotal, 2) }}</strong>
        </section>
        <section class="card dashboard-card">
            <h2>Unread notifications</h2>
            <strong>{{ $unreadNotificationCount }}</strong>
        </section>
    </div>

    <section class="card">
        <h2>Recent attendance</h2>
        @if ($recentAttendance->isEmpty())
            <p class="muted">No attendance records in the last 30 days.</p>
        @else
            <ul>
                @foreach ($recentAttendance as $attendanceRecord)
                    <li>
                        {{ $attendanceRecord->check_in_at->format('M j, Y g:i A') }}
                        @if ($attendanceRecord->check_out_at)
                            — checked out {{ $attendanceRecord->check_out_at->format('g:i A') }}
                        @else
                            — check-out not recorded
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

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
        <p><a href="{{ route('member.operating-schedules.index') }}">View weekly hours and all relevant closures</a></p>
    </section>

    <section class="card">
        <h2>Recent payments</h2>
        <p><a href="{{ route('member.payments.index') }}">View payment history</a></p>
        @if ($recentPayments->isEmpty())
            <p class="muted">No payment records are available.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                    <tr><th>Date</th><th>Method</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('M j, Y') }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card">
        <h2>Notifications</h2>
        <p><a href="{{ route('member.notifications.index') }}">View notification history and delivery status</a></p>
        @if ($notifications->isEmpty())
            <p class="muted">No notifications are available.</p>
        @else
            <ul>
                @foreach ($notifications as $notification)
                    <li>
                        <strong>{{ $notification->title }}</strong>
                        — {{ $notification->message }}
                        <span class="muted">({{ $notification->created_at->format('M j, Y') }}{{ $notification->is_read ? ', read' : ', unread' }})</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card">
        <h2>Announcements</h2>
        @if ($announcements->isEmpty())
            <p class="muted">No announcements are available.</p>
        @else
            <ul>
                @foreach ($announcements as $announcement)
                    <li><strong>{{ $announcement->title }}</strong> — {{ $announcement->message }}</li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
