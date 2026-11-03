@extends('layouts.account')

@section('title', 'Choose a new password')

@section('content')
    <h1>Choose a new password</h1>
    <form method="post" action="{{ route('password.update') }}" class="card auth-form js-loading-form" aria-label="Choose a new password">
        @csrf
        @method('put')
        <input type="hidden" name="token" value="{{ $token }}">
        @error('token')
            <p class="field-error" role="alert">{{ $message }}</p>
        @enderror
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required maxlength="190" autocomplete="email" @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p class="field-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" aria-describedby="password-help{{ $errors->has('password') ? ' password-error' : '' }}" @if ($errors->has('password')) aria-invalid="true" @endif>
            <p class="auth-help" id="password-help">Use at least 12 characters.</p>
            @error('password')
                <p class="field-error" id="password-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="password-confirmation-error" @endif>
            @error('password_confirmation')
                <p class="field-error" id="password-confirmation-error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" data-loading-label="Resetting password...">Reset password</button>
    </form>
@endsection
