<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as BaseTestCase;

class PaymentManagementTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('members');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->bigIncrements('user_id');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->string('role', 20);
            $table->string('account_status', 20);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->bigIncrements('member_id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 30);
            $table->date('date_of_birth')->nullable();
            $table->string('member_status', 20);
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users');
        });

        Schema::create('membership_plans', function (Blueprint $table): void {
            $table->bigIncrements('plan_id');
            $table->string('plan_name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 10, 2);
            $table->string('plan_status', 20);
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table): void {
            $table->bigIncrements('membership_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('plan_name_snapshot', 120);
            $table->decimal('plan_price_snapshot', 10, 2);
            $table->unsignedInteger('plan_duration_snapshot');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('membership_status', 20);
            $table->timestamps();
            $table->foreign('member_id')->references('member_id')->on('members');
            $table->foreign('plan_id')->references('plan_id')->on('membership_plans');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->bigIncrements('payment_id');
            $table->unsignedBigInteger('membership_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 20);
            $table->string('reference_number', 150)->nullable();
            $table->dateTime('payment_date');
            $table->unsignedBigInteger('received_by');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('membership_id')->references('membership_id')->on('memberships');
            $table->foreign('received_by')->references('user_id')->on('users');
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->bigIncrements('refund_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('requested_by');
            $table->decimal('requested_amount', 10, 2);
            $table->text('reason');
            $table->string('refund_status', 20)->default('Pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('payment_id')->references('payment_id')->on('payments');
            $table->foreign('requested_by')->references('user_id')->on('users');
            $table->foreign('reviewed_by')->references('user_id')->on('users');
        });
    }

    public function test_staff_records_payment_and_admin_can_view_details_and_print_receipt(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $membership = $this->makeMembership($this->makeMember());
        $paymentDate = now()->subHour()->format('Y-m-d\TH:i');

        $this->actingAs($staff)
            ->get(route('staff.payments.create'))
            ->assertOk()
            ->assertSee('Record Payment');

        $this->post(route('staff.payments.store'), [
            'membership_id' => $membership->membership_id,
            'amount' => '75.25',
            'payment_method' => 'GCash',
            'reference_number' => 'GC-750',
            'payment_date' => $paymentDate,
            'notes' => 'Front desk receipt',
        ])->assertRedirect();

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('75.25', $payment->amount);
        $this->assertSame('GCash', $payment->payment_method);
        $this->assertSame('GC-750', $payment->reference_number);
        $this->assertSame('Front desk receipt', $payment->notes);
        $this->assertSame($staff->user_id, $payment->received_by);

        $this->actingAs($this->makeUser('Admin', 'admin@example.test'))
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('75.25')
            ->assertSee('GC-750');
        $this->actingAs($staff)
            ->get(route('staff.payments.show', $payment))
            ->assertOk()
            ->assertSee('Payment Details')
            ->assertSee('Remaining Refund Balance');
        $this->get(route('staff.payments.receipt', $payment))
            ->assertOk()
            ->assertSee('Payment Receipt')
            ->assertSee('Print Receipt')
            ->assertSee('GC-750');
    }

    public function test_payment_validation_rejects_nonpositive_amount_invalid_method_and_future_date(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $membership = $this->makeMembership($this->makeMember());

        $this->actingAs($staff)
            ->from(route('staff.payments.create'))
            ->post(route('staff.payments.store'), [
                'membership_id' => $membership->membership_id,
                'amount' => '0.00',
                'payment_method' => 'Crypto',
                'payment_date' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('staff.payments.create'))
            ->assertSessionHasErrors(['amount', 'payment_method', 'payment_date']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_members_can_only_view_their_own_payment_history_and_receipts(): void
    {
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $ownMember = $this->makeMember($memberUser);
        $otherMember = $this->makeMember($this->makeUser('Member', 'other@example.test'));
        $ownPayment = $this->makePayment($this->makeMembership($ownMember), 'OWN-REF');
        $otherPayment = $this->makePayment($this->makeMembership($otherMember), 'OTHER-REF');

        $this->actingAs($memberUser)
            ->get(route('member.payments.index'))
            ->assertOk()
            ->assertSee('OWN-REF')
            ->assertDontSee('OTHER-REF');

        $this->get(route('member.payments.show', $ownPayment))
            ->assertOk()
            ->assertSee('OWN-REF');
        $this->get(route('member.payments.receipt', $ownPayment))
            ->assertOk()
            ->assertSee('Payment Receipt')
            ->assertSee('OWN-REF');
        $this->get(route('member.payments.show', $otherPayment))->assertNotFound();
        $this->get(route('member.payments.receipt', $otherPayment))->assertNotFound();
        $this->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_staff_can_request_partial_refunds_and_admin_can_approve_a_lower_amount(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $payment = $this->makePayment($this->makeMembership($this->makeMember()), 'REFUND-1', '100.00');

        $this->actingAs($staff)
            ->get(route('staff.payments.refunds.create', $payment))
            ->assertOk()
            ->assertSee('Remaining refundable balance');

        $this->post(route('staff.payments.refunds.store', $payment), [
            'requested_amount' => '50.00',
            'reason' => 'Service issue',
        ])->assertRedirect();

        $refund = Refund::query()->firstOrFail();
        $this->assertSame('Pending', $refund->refund_status);
        $this->assertSame('50.00', $refund->requested_amount);
        $this->assertSame($staff->user_id, $refund->requested_by);

        $this->actingAs($admin)
            ->get(route('admin.refunds.review', $refund))
            ->assertOk()
            ->assertSee('Approve Refund');

        $this->post(route('admin.refunds.approve', $refund), [
            'approved_amount' => '30.00',
            'decision_reason' => 'Partial refund approved',
        ])->assertRedirect(route('admin.refunds.show', $refund));

        $this->assertSame('Approved', $refund->fresh()->refund_status);
        $this->assertSame('30.00', $refund->fresh()->approved_amount);
        $this->assertSame($admin->user_id, $refund->fresh()->reviewed_by);
        $this->assertSame('70.00', app(RefundService::class)->remainingBalance($payment));
        $this->get(route('admin.refunds.show', $refund))
            ->assertOk()
            ->assertSee('Partial refund approved');
    }

    public function test_cumulative_approved_refunds_cannot_exceed_payment_and_admin_can_reduce_second_approval(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $payment = $this->makePayment($this->makeMembership($this->makeMember()), 'REFUND-2', '100.00');
        $this->actingAs($staff);

        $this->post(route('staff.payments.refunds.store', $payment), [
            'requested_amount' => '80.00',
            'reason' => 'First refund',
        ])->assertRedirect();
        $firstRefund = Refund::query()->firstOrFail();

        $this->post(route('staff.payments.refunds.store', $payment), [
            'requested_amount' => '50.00',
            'reason' => 'Second refund',
        ])->assertRedirect();
        $secondRefund = Refund::query()->where('refund_id', '!=', $firstRefund->refund_id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $firstRefund), [
                'approved_amount' => '80.00',
            ])->assertRedirect();

        $this->post(route('admin.refunds.approve', $secondRefund), [
            'approved_amount' => '50.00',
        ])->assertSessionHasErrors('approved_amount');
        $this->assertSame('Pending', $secondRefund->fresh()->refund_status);

        $this->post(route('admin.refunds.approve', $secondRefund), [
            'approved_amount' => '20.00',
        ])->assertRedirect();

        $this->assertSame('100.00', app(RefundService::class)->approvedRefundTotal($payment));
        $this->assertSame('0.00', app(RefundService::class)->remainingBalance($payment));
        $this->actingAs($staff);
        $this->from(route('staff.payments.show', $payment))
            ->post(route('staff.payments.refunds.store', $payment), [
                'requested_amount' => '0.01',
                'reason' => 'No balance',
            ])
            ->assertRedirect(route('staff.payments.show', $payment))
            ->assertSessionHasErrors('requested_amount');
    }

    public function test_refund_requests_reject_amounts_above_remaining_balance_and_admin_can_reject(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $payment = $this->makePayment($this->makeMembership($this->makeMember()), 'REFUND-3', '100.00');
        $approvedRefund = $this->makeRefund($payment, $staff, '60.00', 'Previously approved');
        $approvedRefund->refund_status = 'Approved';
        $approvedRefund->approved_amount = '60.00';
        $approvedRefund->reviewed_by = $admin->user_id;
        $approvedRefund->reviewed_at = now();
        $approvedRefund->save();

        $this->actingAs($staff)
            ->from(route('staff.payments.refunds.create', $payment))
            ->post(route('staff.payments.refunds.store', $payment), [
                'requested_amount' => '50.00',
                'reason' => 'Exceeds remaining balance',
            ])
            ->assertRedirect(route('staff.payments.refunds.create', $payment))
            ->assertSessionHasErrors('requested_amount');

        $this->post(route('staff.payments.refunds.store', $payment), [
            'requested_amount' => '30.00',
            'reason' => 'Further service issue',
        ])->assertRedirect();
        $pendingRefund = Refund::query()->where('refund_status', 'Pending')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.refunds.reject', $pendingRefund), [
                'decision_reason' => 'Not eligible',
            ])
            ->assertRedirect(route('admin.refunds.show', $pendingRefund));

        $this->assertSame('Rejected', $pendingRefund->fresh()->refund_status);
        $this->assertNull($pendingRefund->fresh()->approved_amount);
        $this->assertSame('Not eligible', $pendingRefund->fresh()->decision_reason);
    }

    public function test_only_admin_can_approve_refunds_and_staff_only_see_their_own_requests(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $anotherStaff = $this->makeUser('Staff', 'another-staff@example.test');
        $member = $this->makeMember();
        $payment = $this->makePayment($this->makeMembership($member), 'REFUND-4');
        $refund = $this->makeRefund($payment, $staff, '10.00', 'Staff request');

        $this->actingAs($anotherStaff)
            ->get(route('staff.refunds.show', $refund))
            ->assertNotFound();
        $this->get(route('staff.refunds.index'))
            ->assertOk()
            ->assertDontSee('Staff request');
        $this->post(route('admin.refunds.approve', $refund), [
            'approved_amount' => '10.00',
        ])->assertForbidden();

        $memberUser = $member->user;
        $this->actingAs($memberUser)
            ->get(route('staff.refunds.index'))
            ->assertForbidden();
        $this->get(route('admin.refunds.index'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_from_payment_and_refund_pages(): void
    {
        $this->get(route('admin.payments.index'))->assertRedirect('/login');
        $this->get(route('staff.refunds.index'))->assertRedirect('/login');
        $this->get(route('member.payments.index'))->assertRedirect('/login');
    }

    public function test_payment_and_refund_routes_have_no_delete_endpoint(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (str_contains($route->uri(), 'payment') || str_contains($route->uri(), 'refund')) {
                $this->assertNotContains('DELETE', $route->methods());
            }
        }
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $email,
            'password' => Hash::make('test-password-123'),
            'role' => $role,
            'account_status' => 'Active',
        ]);
    }

    private function makeMember(?User $user = null): Member
    {
        $user ??= $this->makeUser('Member', 'member@example.test');

        return Member::create([
            'user_id' => $user->user_id,
            'first_name' => 'Member',
            'last_name' => $user->last_name,
            'phone' => '555-0000',
            'member_status' => 'Active',
        ]);
    }

    private function makeMembership(Member $member): Membership
    {
        $plan = MembershipPlan::create([
            'plan_name' => 'Monthly',
            'duration_days' => 30,
            'price' => '100.00',
            'plan_status' => 'Active',
        ]);

        return Membership::create([
            'member_id' => $member->member_id,
            'plan_id' => $plan->plan_id,
            'plan_name_snapshot' => $plan->plan_name,
            'plan_price_snapshot' => $plan->price,
            'plan_duration_snapshot' => $plan->duration_days,
            'start_date' => today()->toDateString(),
            'end_date' => today()->copy()->addDays(29)->toDateString(),
            'membership_status' => 'Active',
        ]);
    }

    private function makePayment(
        Membership $membership,
        string $reference = 'PAY-1',
        string $amount = '100.00',
    ): Payment {
        $receiver = $this->makeUser('Staff', 'receiver-'.uniqid().'@example.test');

        return Payment::create([
            'membership_id' => $membership->membership_id,
            'amount' => $amount,
            'payment_method' => 'Cash',
            'reference_number' => $reference,
            'payment_date' => now(),
            'received_by' => $receiver->user_id,
            'notes' => null,
        ]);
    }

    private function makeRefund(
        Payment $payment,
        User $requester,
        string $amount,
        string $reason,
    ): Refund {
        return Refund::create([
            'payment_id' => $payment->payment_id,
            'requested_by' => $requester->user_id,
            'requested_amount' => $amount,
            'reason' => $reason,
            'refund_status' => 'Pending',
        ]);
    }
}
