@extends('layouts.account')

@section('title', 'Membership Plans')

@section('content')
<h1>Membership Plans</h1>
<p>Choose an active plan to request a purchase. Your membership starts only after Staff or Admin confirms payment.</p>

@if ($hasCurrentOrUpcomingMembership)
    <section class="card">
        <p>You already have an active or upcoming membership. Submit a renewal request from your <a href="{{ route('member.memberships.index') }}">membership history</a>.</p>
    </section>
@endif

@if ($plans->isEmpty())
    <section class="card"><p>No active membership plans are available right now.</p></section>
@else
    <div class="dashboard-cards">
        @foreach ($plans as $plan)
            <section class="card dashboard-card">
                <h2>{{ $plan->plan_name }}</h2>
                @if ($plan->description)
                    <p>{{ $plan->description }}</p>
                @endif
                <p>{{ $plan->duration_days }} days · {{ number_format((float) $plan->price, 2) }}</p>
                @unless ($hasCurrentOrUpcomingMembership)
                    <form method="POST" action="{{ route('member.plans.purchase', $plan) }}">
                        @csrf
                        <div class="field">
                            <label for="request_reason_{{ $plan->plan_id }}">Note (optional)</label>
                            <textarea id="request_reason_{{ $plan->plan_id }}" name="request_reason" rows="2" maxlength="2000">{{ old('request_reason') }}</textarea>
                        </div>
                        <button type="submit">Request Purchase</button>
                    </form>
                @endunless
            </section>
        @endforeach
    </div>
    <div class="pagination">{{ $plans->links() }}</div>
@endif
<p><a href="{{ route('member.memberships.index') }}">View membership and request history</a></p>
@endsection
