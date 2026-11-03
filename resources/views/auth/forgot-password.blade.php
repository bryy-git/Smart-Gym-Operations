@extends('layouts.account')

@section('title', 'Forgot password')

@section('content')
    <h1>Reset your password</h1>
    <form method="post" action="{{ route('password.email') }}" class="card auth-form js-loading-form" aria-label="Request a password reset">
        @csrf
        <p class="auth-help">Enter your account email. If an active account matches, we will send a reset link.</p>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p class="field-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" data-loading-label="Sending...">Send reset link</button>
        <p class="auth-help"><a href="{{ route('login') }}">Back to log in</a></p>
    </form>
@endsection
