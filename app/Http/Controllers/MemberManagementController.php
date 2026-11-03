<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberManagementController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
        ]);

        $query = Member::query()->with('user');

        if (! empty($validated['search'])) {
            $term = mb_strtolower($validated['search']);
            $pattern = '%'.$term.'%';
            $query->where(function (Builder $members) use ($pattern): void {
                $members
                    ->whereRaw('LOWER(members.first_name) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(members.last_name) LIKE ?', [$pattern])
                    ->orWhereHas('user', function (Builder $users) use ($pattern): void {
                        $users->whereRaw('LOWER(email) LIKE ?', [$pattern]);
                    });
            });
        }

        if (! empty($validated['status'])) {
            $query->where('member_status', $validated['status']);
        }

        $members = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('member_id')
            ->paginate(10)
            ->appends($request->query());

        return view('members.index', [
            'layout' => $this->layout($request),
            'routePrefix' => $this->routePrefix($request),
            'members' => $members,
            'search' => $validated['search'] ?? '',
            'status' => $validated['status'] ?? '',
            'hasFilters' => filled($validated['search'] ?? null) || filled($validated['status'] ?? null),
        ]);
    }

    public function create(Request $request): View
    {
        return view('members.form', [
            'layout' => $this->layout($request),
            'routePrefix' => $this->routePrefix($request),
            'mode' => 'create',
            'member' => null,
            'user' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'phone' => ['required', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $member = DB::transaction(function () use ($validated, $request): Member {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'Member',
                'account_status' => 'Active',
            ]);

            return Member::create([
                'user_id' => $user->user_id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'member_status' => 'Active',
                'created_by' => $request->user()->user_id,
            ]);
        });

        return redirect()
            ->route($this->routePrefix($request).'members.show', $member)
            ->with('success', 'Member created successfully.');
    }

    public function show(Request $request, Member $member): View
    {
        $member->load('user');

        return view('members.show', [
            'layout' => $this->layout($request),
            'routePrefix' => $this->routePrefix($request),
            'member' => $member,
            'user' => $member->user,
            'statusesMismatch' => $this->statusesMismatch($member),
        ]);
    }

    public function edit(Request $request, Member $member): View
    {
        $member->load('user');

        return view('members.form', [
            'layout' => $this->layout($request),
            'routePrefix' => $this->routePrefix($request),
            'mode' => 'edit',
            'member' => $member,
            'user' => $member->user,
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $member->load('user');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($member->user->user_id, 'user_id'),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($member, $validated): void {
            $lockedMember = Member::query()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user = User::query()
                ->whereKey($lockedMember->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $user->first_name = $validated['first_name'];
            $user->last_name = $validated['last_name'];
            $user->email = $validated['email'];
            $user->save();

            $lockedMember->first_name = $validated['first_name'];
            $lockedMember->last_name = $validated['last_name'];
            $lockedMember->phone = $validated['phone'];
            $lockedMember->date_of_birth = $validated['date_of_birth'] ?? null;
            $lockedMember->save();
        });

        return redirect()
            ->route($this->routePrefix($request).'members.show', $member)
            ->with('success', 'Member updated successfully.');
    }

    public function confirmStatus(Request $request, Member $member, string $targetStatus): View
    {
        abort_unless(in_array($targetStatus, ['Active', 'Inactive'], true), 404);
        $member->load('user');

        return view('members.status-confirmation', [
            'layout' => $this->layout($request),
            'routePrefix' => $this->routePrefix($request),
            'member' => $member,
            'user' => $member->user,
            'targetStatus' => $targetStatus,
            'targetAccountStatus' => $targetStatus === 'Inactive' ? 'Disabled' : 'Active',
            'statusesMismatch' => $this->statusesMismatch($member),
        ]);
    }

    public function updateStatus(Request $request, Member $member, string $targetStatus): RedirectResponse
    {
        abort_unless(in_array($targetStatus, ['Active', 'Inactive'], true), 404);

        $request->validate([
            'confirm_status_change' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($member, $targetStatus): void {
            $lockedMember = Member::query()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user = User::query()
                ->whereKey($lockedMember->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedMember->member_status = $targetStatus;
            $user->account_status = $targetStatus === 'Inactive' ? 'Disabled' : 'Active';

            $lockedMember->save();
            $user->save();
        });

        return redirect()
            ->route($this->routePrefix($request).'members.show', $member)
            ->with('success', 'Member and user account statuses updated successfully.');
    }

    private function layout(Request $request): string
    {
        return $this->routePrefix($request) === 'staff.' ? 'layouts.staff' : 'layouts.admin';
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'staff.')
            ? 'staff.'
            : 'admin.';
    }

    private function statusesMismatch(Member $member): bool
    {
        return ! (
            ($member->member_status === 'Active' && $member->user->account_status === 'Active')
            || ($member->member_status === 'Inactive' && $member->user->account_status === 'Disabled')
        );
    }
}
