@extends($routePrefix === 'member.' ? 'layouts.account' : ($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin'))

@section('title', 'Notification History')

@section('content')
<p class="breadcrumb">{{ ucfirst(trim($routePrefix, '.')) }} / Notification History</p>
<h1>Notification History</h1>
<section class="card">
    @if ($notifications->isEmpty())
        <p>No notifications are available.</p>
    @else
        @foreach ($notifications as $notification)
            <article class="card">
                <div class="page-heading">
                    <h2>{{ $notification->title }}</h2>
                    <span class="status">{{ $notification->is_read ? 'Read' : 'Unread' }}</span>
                </div>
                <p>{{ $notification->message }}</p>
                <p class="muted">{{ $notification->notification_type }} · {{ $notification->created_at->format('M j, Y g:i A') }}</p>
                <h3>Delivery status</h3>
                <ul>
                    @forelse ($notification->deliveries as $delivery)
                        <li>
                            {{ $delivery->delivery_channel }}: {{ $delivery->delivery_status }}
                            @if ($delivery->sent_at)
                                ({{ $delivery->sent_at->format('M j, Y g:i A') }})
                            @endif
                        </li>
                    @empty
                        <li>Delivery tracking is unavailable for this notification.</li>
                    @endforelse
                </ul>
                <div class="button-row">
                    @unless ($notification->is_read)
                        <form method="POST" action="{{ route($routePrefix.'notifications.read', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit">Mark as read</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route($routePrefix.'notifications.destroy', $notification) }}">
                        @csrf
                        @method('DELETE')
                        <button class="danger" type="submit">Delete notification</button>
                    </form>
                </div>
            </article>
        @endforeach
        <div class="pagination">{{ $notifications->links() }}</div>
    @endif
</section>
@endsection
