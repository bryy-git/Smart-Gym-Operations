<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function approvedRefundTotal(Payment $payment): string
    {
        $approvedCents = $payment->refunds()
            ->where('refund_status', 'Approved')
            ->sum('approved_amount');

        return $this->formatCents($this->toCents($approvedCents));
    }

    public function remainingBalance(Payment $payment): string
    {
        $approvedCents = $payment->refunds()
            ->where('refund_status', 'Approved')
            ->sum('approved_amount');

        return $this->formatCents(max(
            0,
            $this->toCents($payment->amount) - $this->toCents($approvedCents),
        ));
    }

    public function submitRequest(
        Payment $payment,
        User $requester,
        string $requestedAmount,
        string $reason,
    ): Refund {
        return DB::transaction(function () use ($payment, $requester, $requestedAmount, $reason): Refund {
            $lockedPayment = Payment::query()
                ->whereKey($payment->payment_id)
                ->lockForUpdate()
                ->firstOrFail();
            $requestedCents = $this->toCents($requestedAmount);
            $remainingCents = $this->toCents($this->remainingBalance($lockedPayment));

            if ($requestedCents <= 0 || $requestedCents > $remainingCents) {
                throw ValidationException::withMessages([
                    'requested_amount' => 'The requested refund must be positive and no greater than the unrefunded payment balance.',
                ]);
            }

            return Refund::create([
                'payment_id' => $lockedPayment->payment_id,
                'requested_by' => $requester->user_id,
                'requested_amount' => $this->formatCents($requestedCents),
                'reason' => $reason,
                'refund_status' => 'Pending',
            ]);
        });
    }

    public function approve(
        Refund $refund,
        User $reviewer,
        string $approvedAmount,
        ?string $decisionReason,
    ): Refund {
        return DB::transaction(function () use (
            $refund,
            $reviewer,
            $approvedAmount,
            $decisionReason,
        ): Refund {
            $lockedRefund = Refund::query()
                ->whereKey($refund->refund_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRefund->refund_status !== 'Pending') {
                abort(409, 'This refund request has already been reviewed.');
            }

            $payment = Payment::query()
                ->whereKey($lockedRefund->payment_id)
                ->lockForUpdate()
                ->firstOrFail();
            $approvedCents = $this->toCents($approvedAmount);
            $requestedCents = $this->toCents($lockedRefund->requested_amount);
            $remainingCents = $this->toCents($this->remainingBalance($payment));

            if ($approvedCents <= 0 || $approvedCents > $requestedCents) {
                throw ValidationException::withMessages([
                    'approved_amount' => 'The approved amount must be positive and no greater than the requested amount.',
                ]);
            }

            if ($approvedCents > $remainingCents) {
                throw ValidationException::withMessages([
                    'approved_amount' => 'The approved amount exceeds the remaining refundable balance.',
                ]);
            }

            $lockedRefund->refund_status = 'Approved';
            $lockedRefund->approved_amount = $this->formatCents($approvedCents);
            $lockedRefund->reviewed_by = $reviewer->user_id;
            $lockedRefund->reviewed_at = now();
            $lockedRefund->decision_reason = $decisionReason;
            $lockedRefund->save();

            return $lockedRefund;
        });
    }

    public function reject(Refund $refund, User $reviewer, ?string $decisionReason): Refund
    {
        return DB::transaction(function () use ($refund, $reviewer, $decisionReason): Refund {
            $lockedRefund = Refund::query()
                ->whereKey($refund->refund_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRefund->refund_status !== 'Pending') {
                abort(409, 'This refund request has already been reviewed.');
            }

            $lockedRefund->refund_status = 'Rejected';
            $lockedRefund->approved_amount = null;
            $lockedRefund->reviewed_by = $reviewer->user_id;
            $lockedRefund->reviewed_at = now();
            $lockedRefund->decision_reason = $decisionReason;
            $lockedRefund->save();

            return $lockedRefund;
        });
    }

    private function toCents(string|int|float|null $amount): int
    {
        $parts = explode('.', (string) $amount, 2);
        $whole = (int) ($parts[0] ?? 0);
        $fraction = (int) str_pad(substr($parts[1] ?? '', 0, 2), 2, '0');

        return ($whole * 100) + $fraction;
    }

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
