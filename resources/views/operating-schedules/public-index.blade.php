@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.account')

@section('title', 'Operating Hours & Closures')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Operating Hours</p>
<h1>Operating Hours</h1>
<section class="card">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Day</th><th>Hours</th></tr></thead>
            <tbody>
            @foreach ($dayNames as $day => $dayName)
                @php($daySchedules = $schedules->get($day, collect()))
                <tr>
                    <th scope="row">{{ $dayName }}</th>
                    <td>
                        @if ($daySchedules->isEmpty())
                            Closed
                        @else
                            @foreach ($daySchedules as $schedule)
                                {{ substr($schedule->opening_time, 0, 5) }}–{{ substr($schedule->closing_time, 0, 5) }}@if (! $loop->last), @endif
                            @endforeach
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Upcoming and active closure announcements</h2>
    @if ($closures->isEmpty())
        <p class="muted">There are no upcoming or active closure announcements.</p>
    @else
        @foreach ($closures as $closure)
            <article>
                <h3>{{ $closure->title }} <span class="status">{{ $closure->display_status }}</span></h3>
                <p><strong>{{ $closure->start_datetime->format('M j, Y g:i A') }} – {{ $closure->end_datetime->format('M j, Y g:i A') }}</strong></p>
                @if ($closure->reason)
                    <p>{{ $closure->reason }}</p>
                @endif
            </article>
        @endforeach
    @endif
</section>
@endsection
