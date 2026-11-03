@extends('layouts.account')

@section('title', 'Log in')

@section('content')
    <div class="auth-login-layout">
        <section class="auth-showcase" aria-label="SmartGym member access" style="--auth-hero-image: url('{{ asset('images/gym-hero.jpg') }}')">
            <span class="auth-showcase-kicker">SmartGym Operations</span>
            <h1>Good to see you.<span>Your gym is in sync.</span></h1>
            
            
        </section>

        <section class="auth-login-panel">
            <div class="auth-login-heading">
                <h2>Log in</h2>
                <p>Enter your account details to continue.</p>
            </div>
            <form method="post" action="{{ route('login.store') }}" class="auth-login-form js-loading-form" aria-label="Log in to SmartGym">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="username" @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                    @error('email')
                        <p class="field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                    @error('password')
                        <p class="field-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" data-loading-label="Signing in...">Log in</button>
                <p class="auth-help"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
            </form>
        </section>
    </div>
@endsection
