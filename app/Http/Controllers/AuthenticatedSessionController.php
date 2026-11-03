<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        $user = $this->activeAuthenticatedUser($request);

        if ($user !== null) {
            return $this->redirectForRole($user);
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->activeAuthenticatedUser($request);

        if ($user !== null) {
            return $this->redirectForRole($user);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => trim($validated['email']),
            'password' => $validated['password'],
            'account_status' => 'Active',
        ])) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are invalid.',
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectForRole($request->user());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function redirectForRole(?User $user): RedirectResponse
    {
        return match ($user?->role) {
            'Admin' => redirect()->route('admin.dashboard'),
            'Staff' => redirect()->route('staff.dashboard'),
            'Member' => redirect()->route('member.dashboard'),
            default => redirect()->route('profile.edit'),
        };
    }

    private function activeAuthenticatedUser(Request $request): ?User
    {
        $user = Auth::user();

        if ($user instanceof User && $user->account_status === 'Active') {
            return $user;
        }

        if ($user !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return null;
    }
}
