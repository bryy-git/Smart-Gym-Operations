@extends('layouts.account')

@section('title', 'My Membership Requests')

@section('content')
<h1>My Membership Requests</h1>
<p><a href="{{ route('member.memberships.index') }}">Back to membership history</a></p>
<section class="card">
    @if ($requests->isEmpty())
        <p>No purchase or renewal requests have been submitted.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Type</th><th>Plan</th><th>Note</th><th>Requested</th><th>Status</th><th>Decision</th></tr></thead>
                <tbody>
                @foreach ($requests as $membershipRequest)
                    <tr>
                        <td>{{ $membershipRequest->request_type }}</td>
                        <td>{{ $membershipRequest->plan?->plan_name ?? 'Unavailable' }}</td>
                        <td>{{ $membershipRequest->request_reason ?? '—' }}</td>
                        <td>{{ $membershipRequest->requested_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $membershipRequest->request_status }}</td>
                        <td>{{ $membershipRequest->decision_reason ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $requests->links() }}</div>
    @endif
</section>
@endsection
