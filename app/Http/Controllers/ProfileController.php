<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'member' => $user->role === 'Member' ? $user->member : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $member = $user->role === 'Member' ? $user->member : null;

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
        ];

        if ($member !== null) {
            $rules['phone'] = ['required', 'string', 'max:30'];
            $rules['date_of_birth'] = ['nullable', 'date', 'before_or_equal:today'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($user, $member, $validated): void {
            $lockedUser = User::query()
                ->whereKey($user->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->first_name = $validated['first_name'];
            $lockedUser->last_name = $validated['last_name'];
            $lockedUser->email = trim($validated['email']);
            $lockedUser->save();

            if ($member !== null) {
                $lockedMember = Member::query()
                    ->whereKey($member->member_id)
                    ->where('user_id', $lockedUser->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedMember->first_name = $validated['first_name'];
                $lockedMember->last_name = $validated['last_name'];
                $lockedMember->phone = $validated['phone'];
                $lockedMember->date_of_birth = $validated['date_of_birth'] ?? null;
                $lockedMember->save();
            }
        });

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                Password::min(12),
                'confirmed',
                'different:current_password',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->password = $validated['password'];
        $user->save();

        $request->session()->regenerate();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Your password has been changed.');
    }
}
