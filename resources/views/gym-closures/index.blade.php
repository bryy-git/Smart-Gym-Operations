@extends('layouts.admin')

@section('title', 'Gym Closures')

@section('content')
<p class="breadcrumb"><a href="{{ route('admin.operating-schedules.index') }}">Operating Schedule</a> / Gym Closures</p>
<div class="page-heading">
    <h1>Gym Closures</h1>
    <a class="button" href="{{ route('admin.gym-closures.create') }}">Announce a closure</a>
</div>

<section class="card">
    @if ($closures->isEmpty())
        <p>No closures have been announced.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Closure</th><th>Period</th><th>Status</th><th>Created by</th><th></th></tr></thead>
                <tbody>
                @foreach ($closures as $closure)
                    <tr>
                        <td>
                            <strong>{{ $closure->title }}</strong>
                            @if ($closure->reason)
                                <br>{{ $closure->reason }}
                            @endif
                        </td>
                        <td>{{ $closure->start_datetime->format('M j, Y g:i A') }} – {{ $closure->end_datetime->format('M j, Y g:i A') }}</td>
                        <td><span class="status">{{ $closure->display_status }}</span></td>
                        <td>{{ $closure->creator?->first_name }} {{ $closure->creator?->last_name }}</td>
                        <td><a href="{{ route('admin.gym-closures.edit', $closure) }}">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $closures->links() }}</div>
    @endif
</section>
@endsection
