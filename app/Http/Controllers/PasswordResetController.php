<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function storeLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $user = User::query()
            ->where('email', trim($validated['email']))
            ->where('account_status', 'Active')
            ->first();

        if ($user !== null) {
            $token = $this->passwordResetService->issue($user);
            $user->notify(new ResetPassword($token));
        }

        return back()->with(
            'status',
            'If an active account exists for that email address, a password reset link has been sent.',
        );
    }

    public function edit(Request $request, string $token): View
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $validated['email'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', Password::min(12), 'confirmed'],
        ]);

        if (! $this->passwordResetService->reset(
            trim($validated['email']),
            $validated['token'],
            $validated['password'],
        )) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'token' => 'This password reset link is invalid or has expired.',
                ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }
}
