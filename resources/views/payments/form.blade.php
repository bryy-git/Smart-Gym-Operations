@extends($routePrefix === 'staff.' ? 'layouts.staff' : 'layouts.admin')

@section('title', 'Record Payment')

@section('content')
<p class="breadcrumb"><a href="{{ route($routePrefix.'payments.index') }}">Payments</a> / Record Payment</p>
<h1>Record Payment</h1>
<section class="card">
    @if ($memberships->isEmpty())
        <p>No membership records are available for payment recording.</p>
    @else
        <form method="POST" action="{{ route($routePrefix.'payments.store') }}">
            @csrf
            <div class="field">
                <label for="membership_id">Membership</label>
                <select id="membership_id" name="membership_id" required>
                    <option value="">Select a membership</option>
                    @foreach ($memberships as $membership)
                        <option value="{{ $membership->membership_id }}" @selected((string) old('membership_id') === (string) $membership->membership_id)>
                            {{ $membership->member->first_name }} {{ $membership->member->last_name }}
                            — {{ $membership->plan_name_snapshot }}
                            — {{ $membership->start_date->format('M j, Y') }} to {{ $membership->end_date->format('M j, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="amount">Amount</label>
                <input id="amount" name="amount" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ old('amount') }}" required>
            </div>
            <div class="field">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">Select a method</option>
                    @foreach (['Cash', 'GCash', 'Card', 'Other'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="reference_number">Reference Number (optional)</label>
                <input id="reference_number" name="reference_number" maxlength="150" value="{{ old('reference_number') }}">
            </div>
            <div class="field">
                <label for="payment_date">Payment Date and Time</label>
                <input id="payment_date" name="payment_date" type="datetime-local" value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
            </div>
            <div class="field">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes') }}</textarea>
            </div>
            <div class="button-row">
                <button type="submit">Save Payment</button>
                <a class="button secondary" href="{{ route($routePrefix.'payments.index') }}">Cancel</a>
            </div>
        </form>
    @endif
</section>
@endsection
