@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Membership Requests')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Membership Requests</p>
<h1>Membership Requests</h1>

<section class="card">
    <form class="filters" method="GET" action="{{ route($routePrefix.'membership-requests.index') }}">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            @foreach (['Pending', 'Approved', 'Rejected'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <label for="type">Type</label>
        <select id="type" name="type">
            <option value="">All</option>
            @foreach (['Purchase', 'Renewal'] as $option)
                <option value="{{ $option }}" @selected($type === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>
</section>

<section class="card">
    @if ($requests->isEmpty())
        <p>No membership requests match these filters.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Member</th><th>Type</th><th>Plan</th><th>Note</th><th>Requested</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($requests as $membershipRequest)
                    <tr>
                        <td>{{ $membershipRequest->member->first_name }} {{ $membershipRequest->member->last_name }}<br>{{ $membershipRequest->member->user->email }}</td>
                        <td>{{ $membershipRequest->request_type }}</td>
                        <td>{{ $membershipRequest->plan?->plan_name ?? 'Unavailable' }}</td>
                        <td>{{ $membershipRequest->request_reason ?? '—' }}</td>
                        <td>{{ $membershipRequest->requested_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $membershipRequest->request_status }}</td>
                        <td>
                            @if ($membershipRequest->request_status === 'Pending')
                                <a href="{{ route($routePrefix.'membership-requests.review', $membershipRequest) }}">Review</a>
                            @else
                                {{ $membershipRequest->decision_reason ?? 'Reviewed' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $requests->links() }}</div>
    @endif
</section>
@endsection
