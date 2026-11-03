<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Services\RefundService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'method' => ['nullable', Rule::in(['Cash', 'GCash', 'Card', 'Other'])],
        ]);
        $routePrefix = $this->routePrefix($request);

        $payments = Payment::query()
            ->with(['membership.member.user', 'receiver'])
            ->when(
                ! empty($validated['search']),
                function (Builder $query) use ($validated): void {
                    $pattern = '%'.mb_strtolower($validated['search']).'%';
                    $query->where(function (Builder $payments) use ($pattern): void {
                        $payments
                            ->whereRaw('LOWER(reference_number) LIKE ?', [$pattern])
                            ->orWhereHas('membership', function (Builder $memberships) use ($pattern): void {
                                $memberships
                                    ->whereRaw('LOWER(plan_name_snapshot) LIKE ?', [$pattern])
                                    ->orWhereHas('member', function (Builder $members) use ($pattern): void {
                                        $members
                                            ->whereRaw('LOWER(first_name) LIKE ?', [$pattern])
                                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$pattern])
                                            ->orWhereHas('user', function (Builder $users) use ($pattern): void {
                                                $users->whereRaw('LOWER(email) LIKE ?', [$pattern]);
                                            });
                                    });
                            });
                    });
                },
            )
            ->when(
                ! empty($validated['method']),
                fn (Builder $query): Builder => $query->where('payment_method', $validated['method']),
            )
            ->orderByDesc('payment_date')
            ->orderByDesc('payment_id')
            ->paginate(15)
            ->appends($request->query());

        return view('payments.index', [
            'routePrefix' => $routePrefix,
            'payments' => $payments,
            'search' => $validated['search'] ?? '',
            'method' => $validated['method'] ?? '',
        ]);
    }

    public function create(Request $request): View
    {
        return view('payments.form', [
            'routePrefix' => $this->routePrefix($request),
            'memberships' => Membership::query()
                ->with(['member.user'])
                ->orderByDesc('start_date')
                ->orderByDesc('membership_id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'membership_id' => ['required', 'integer', Rule::exists('memberships', 'membership_id')],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash', 'Card', 'Other'])],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'payment_date' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payment = Payment::create([
            'membership_id' => $validated['membership_id'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'payment_date' => Carbon::parse($validated['payment_date']),
            'received_by' => $request->user()->user_id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route($this->routePrefix($request).'payments.show', $payment)
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Request $request, Payment $payment, RefundService $refundService): View
    {
        $payment->load([
            'membership.member.user',
            'membership.plan',
            'receiver',
            'refunds.requester',
            'refunds.reviewer',
        ]);

        return view('payments.show', [
            'routePrefix' => $this->routePrefix($request),
            'payment' => $payment,
            'approvedRefundTotal' => $refundService->approvedRefundTotal($payment),
            'remainingBalance' => $refundService->remainingBalance($payment),
        ]);
    }

    public function receipt(Request $request, Payment $payment): View
    {
        $payment->load(['membership.member.user', 'receiver']);

        return view('payments.receipt', [
            'payment' => $payment,
            'audience' => ucfirst($this->routePrefix($request) === 'staff.' ? 'staff' : 'admin'),
        ]);
    }

    public function memberIndex(Request $request): View
    {
        $member = $this->memberForUser($request);
        $payments = Payment::query()
            ->with(['membership'])
            ->whereHas('membership', fn (Builder $query): Builder => $query->where('member_id', $member->member_id))
            ->orderByDesc('payment_date')
            ->orderByDesc('payment_id')
            ->paginate(15);

        return view('payments.member-index', [
            'payments' => $payments,
        ]);
    }

    public function memberShow(
        Request $request,
        Payment $payment,
        RefundService $refundService,
    ): View {
        $member = $this->memberForUser($request);
        $payment = Payment::query()
            ->whereKey($payment->payment_id)
            ->whereHas('membership', fn (Builder $query): Builder => $query->where('member_id', $member->member_id))
            ->with('membership')
            ->firstOrFail();

        return view('payments.member-show', [
            'payment' => $payment,
            'approvedRefundTotal' => $refundService->approvedRefundTotal($payment),
        ]);
    }

    public function memberReceipt(Request $request, Payment $payment): View
    {
        $member = $this->memberForUser($request);
        $payment = Payment::query()
            ->whereKey($payment->payment_id)
            ->whereHas('membership', fn (Builder $query): Builder => $query->where('member_id', $member->member_id))
            ->with(['membership.member.user', 'receiver'])
            ->firstOrFail();

        return view('payments.receipt', [
            'payment' => $payment,
            'audience' => 'Member',
        ]);
    }

    private function memberForUser(Request $request): Member
    {
        return Member::query()
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'staff.')
            ? 'staff.'
            : 'admin.';
    }
}
