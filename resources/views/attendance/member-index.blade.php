@extends('layouts.account')

@section('title', 'My Attendance')

@section('content')
<h1>My Attendance</h1>
<section class="card">
    @if ($openAttendance !== null)
        <p>You are currently checked in since {{ $openAttendance->check_in_at->format('M j, Y g:i A') }}.</p>
        <form method="POST" action="{{ route('member.attendance.check-out', $openAttendance) }}">
            @csrf
            <button type="submit">Check out</button>
        </form>
    @elseif ($hasValidMembership)
        <p>You have an active account and a membership valid today.</p>
        <form method="POST" action="{{ route('member.attendance.check-in') }}">
            @csrf
            <button type="submit">Check in</button>
        </form>
    @else
        <p>Check-in requires an active member account and a membership valid today.</p>
    @endif
</section>

<section class="card">
    <h2>Attendance history</h2>
    @if ($attendances->isEmpty())
        <p>No attendance records are available.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Check-in</th><th>Check-out</th><th>Source</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->check_in_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $attendance->check_out_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td>{{ $attendance->attendance_source }}</td>
                        <td>{{ $attendance->check_out_at === null ? 'Open' : 'Closed' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $attendances->links() }}</div>
    @endif
</section>
@endsection
