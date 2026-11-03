@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Plan Details')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'membership-plans.index') }}">Membership Plans</a> / Plan Details</p>
<div class="page-heading">
    <div>
        <h1>{{ $plan->plan_name }}</h1>
        <p>Membership plan details</p>
    </div>
    <a class="button" href="{{ route($routePrefix.'membership-plans.edit', $plan) }}">Edit Plan</a>
</div>
<section class="card">
    <dl>
        <dt>Description</dt><dd>{{ $plan->description ?? '—' }}</dd>
        <dt>Duration</dt><dd>{{ $plan->duration_days }} days</dd>
        <dt>Price</dt><dd>{{ number_format((float) $plan->price, 2) }}</dd>
        <dt>Status</dt><dd>{{ $plan->plan_status }}</dd>
        <dt>Membership records</dt><dd>{{ $plan->memberships_count }}</dd>
        <dt>Requests</dt><dd>{{ $plan->requests_count }}</dd>
        <dt>Created</dt><dd>{{ $plan->created_at?->format('M j, Y') ?? '—' }}</dd>
        <dt>Updated</dt><dd>{{ $plan->updated_at?->format('M j, Y') ?? '—' }}</dd>
    </dl>
</section>
<p><a href="{{ route($routePrefix.'membership-plans.index') }}">Back to plans</a></p>
@endsection
