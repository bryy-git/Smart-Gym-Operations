<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['Pending', 'Approved', 'Rejected'])],
        ]);

        $refunds = Refund::query()
            ->with(['payment.membership.member.user', 'requester', 'reviewer'])
            ->when(
                ! empty($validated['status']),
                fn (Builder $query): Builder => $query->where('refund_status', $validated['status']),
            )
            ->orderByRaw("CASE WHEN refund_status = 'Pending' THEN 0 ELSE 1 END")
            ->orderByDesc('refund_id')
            ->paginate(15)
            ->appends($request->query());

        return view('refunds.admin-index', [
            'refunds' => $refunds,
            'status' => $validated['status'] ?? '',
        ]);
    }

    public function staffIndex(Request $request): View
    {
        $refunds = Refund::query()
            ->with(['payment.membership.member.user', 'reviewer'])
            ->where('requested_by', $request->user()->user_id)
            ->orderByDesc('refund_id')
            ->paginate(15);

        return view('refunds.staff-index', [
            'refunds' => $refunds,
        ]);
    }

    public function create(Request $request, Payment $payment, RefundService $refundService): View
    {
        $payment->load(['membership.member.user', 'membership.plan']);

        return view('refunds.form', [
            'payment' => $payment,
            'remainingBalance' => $refundService->remainingBalance($payment),
        ]);
    }

    public function store(
        Request $request,
        Payment $payment,
        RefundService $refundService,
    ): RedirectResponse {
        $validated = $request->validate([
            'requested_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $refund = $refundService->submitRequest(
            $payment,
            $request->user(),
            $validated['requested_amount'],
            $validated['reason'],
        );

        return redirect()
            ->route('staff.refunds.show', $refund)
            ->with('success', 'Refund request submitted to Admin for review.');
    }

    public function adminShow(Refund $refund, RefundService $refundService): View
    {
        $refund->load(['payment.membership.member.user', 'requester', 'reviewer']);

        return view('refunds.show', [
            'refund' => $refund,
            'remainingBalance' => $refundService->remainingBalance($refund->payment),
            'audience' => 'Admin',
            'routePrefix' => 'admin.',
        ]);
    }

    public function staffShow(Request $request, Refund $refund, RefundService $refundService): View
    {
        $refund = Refund::query()
            ->whereKey($refund->refund_id)
            ->where('requested_by', $request->user()->user_id)
            ->with(['payment.membership.member.user', 'requester', 'reviewer'])
            ->firstOrFail();

        return view('refunds.show', [
            'refund' => $refund,
            'remainingBalance' => $refundService->remainingBalance($refund->payment),
            'audience' => 'Staff',
            'routePrefix' => 'staff.',
        ]);
    }

    public function review(Refund $refund, RefundService $refundService): View
    {
        abort_if($refund->refund_status !== 'Pending', 409);

        $refund->load(['payment.membership.member.user', 'requester']);

        return view('refunds.review', [
            'refund' => $refund,
            'remainingBalance' => $refundService->remainingBalance($refund->payment),
        ]);
    }

    public function approve(
        Request $request,
        Refund $refund,
        RefundService $refundService,
    ): RedirectResponse {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'decision_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $refundService->approve(
            $refund,
            $request->user(),
            $validated['approved_amount'],
            $validated['decision_reason'] ?? null,
        );

        return redirect()
            ->route('admin.refunds.show', $refund)
            ->with('success', 'Refund approved and recorded.');
    }

    public function reject(Request $request, Refund $refund, RefundService $refundService): RedirectResponse
    {
        $validated = $request->validate([
            'decision_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $refundService->reject(
            $refund,
            $request->user(),
            $validated['decision_reason'] ?? null,
        );

        return redirect()
            ->route('admin.refunds.show', $refund)
            ->with('success', 'Refund request rejected.');
    }
}
