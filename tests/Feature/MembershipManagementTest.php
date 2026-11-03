<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\MembershipRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase as BaseTestCase;

class MembershipManagementTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('payments');
        Schema::dropIfExists('membership_requests');
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
            $table->unsignedBigInteger('application_id')->nullable()->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 30);
            $table->date('date_of_birth')->nullable();
            $table->string('member_status', 20);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('created_by')->references('user_id')->on('users');
        });

        Schema::create('membership_plans', function (Blueprint $table): void {
            $table->bigIncrements('plan_id');
            $table->string('plan_name', 120)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 10, 2);
            $table->string('plan_status', 20)->default('Active');
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
            $table->string('membership_status', 20)->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('member_id')->on('members');
            $table->foreign('plan_id')->references('plan_id')->on('membership_plans');
            $table->foreign('created_by')->references('user_id')->on('users');
        });

        Schema::create('membership_requests', function (Blueprint $table): void {
            $table->bigIncrements('request_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->string('request_type', 20);
            $table->string('request_status', 20)->default('Pending');
            $table->text('request_reason')->nullable();
            $table->dateTime('requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->foreign('member_id')->references('member_id')->on('members');
            $table->foreign('membership_id')->references('membership_id')->on('memberships');
            $table->foreign('plan_id')->references('plan_id')->on('membership_plans');
            $table->foreign('requested_by')->references('user_id')->on('users');
            $table->foreign('reviewed_by')->references('user_id')->on('users');
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
            $table->timestamp('created_at')->nullable();
            $table->foreign('membership_id')->references('membership_id')->on('memberships');
            $table->foreign('received_by')->references('user_id')->on('users');
        });
    }

    public function test_admin_and_staff_can_manage_plans_but_members_cannot(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $memberUser = $this->makeUser('Member', 'plan-member@example.test');

        $this->actingAs($staff)
            ->get('/staff/membership-plans')
            ->assertOk()
            ->assertSee('Membership Plans');

        $this->post('/staff/membership-plans', [
            'plan_name' => 'Monthly',
            'description' => 'One month of access',
            'duration_days' => 30,
            'price' => '1250.00',
        ])->assertRedirect(route('staff.membership-plans.index'));

        $plan = MembershipPlan::query()->firstOrFail();
        $historicalMembership = $this->makeMembership(
            $this->makeMember($memberUser),
            $plan,
            snapshotName: 'Monthly Snapshot',
            snapshotPrice: '1200.00',
            snapshotDuration: 30,
        );

        $this->get(route('staff.membership-plans.show', $plan))
            ->assertOk()
            ->assertSee('Membership plan details')
            ->assertSee('Membership records');

        $this->put(route('staff.membership-plans.update', $plan), [
            'plan_name' => 'Monthly Plus',
            'description' => 'Updated description',
            'duration_days' => 31,
            'price' => '1300.00',
        ])->assertRedirect(route('staff.membership-plans.index'));

        $this->assertSame('Monthly Snapshot', $historicalMembership->fresh()->plan_name_snapshot);
        $this->assertSame('1200.00', $historicalMembership->fresh()->plan_price_snapshot);

        $this->patch(route('staff.membership-plans.status.update', $plan), [
            'plan_status' => 'Inactive',
        ])->assertRedirect(route('staff.membership-plans.index'));

        $this->assertSame('Inactive', $plan->fresh()->plan_status);
        $this->actingAs($memberUser)
            ->get('/staff/membership-plans')
            ->assertForbidden();
        $this->post('/staff/membership-plans', [
            'plan_name' => 'Unauthorized',
            'duration_days' => 1,
            'price' => '1.00',
        ])->assertForbidden();
    }

    public function test_plan_validation_rejects_duplicate_names_nonpositive_values_and_extra_precision(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->makePlan('Existing');

        $this->actingAs($admin)
            ->from('/admin/membership-plans/create')
            ->post('/admin/membership-plans', [
                'plan_name' => 'Existing',
                'duration_days' => 0,
                'price' => '1.234',
            ])
            ->assertRedirect('/admin/membership-plans/create')
            ->assertSessionHasErrors(['plan_name', 'duration_days', 'price']);

        $this->assertDatabaseCount('membership_plans', 1);
    }

    public function test_member_purchase_request_is_scoped_and_inactive_plans_cannot_be_requested(): void
    {
        $user = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($user);
        $otherUser = $this->makeUser('Member', 'other@example.test');
        $otherMember = $this->makeMember($otherUser);
        $plan = $this->makePlan('Standard');

        $this->actingAs($user)
            ->post(route('member.plans.purchase', $plan), ['request_reason' => 'Ready to join'])
            ->assertRedirect(route('member.memberships.index'))
            ->assertSessionHas('status');

        $membershipRequest = MembershipRequest::query()->firstOrFail();
        $this->assertSame($member->member_id, $membershipRequest->member_id);
        $this->assertSame($user->user_id, $membershipRequest->requested_by);
        $this->assertSame('Purchase', $membershipRequest->request_type);
        $this->assertSame('Pending', $membershipRequest->request_status);
        $this->get(route('member.plans.index'))
            ->assertOk()
            ->assertSee('Standard');
        $this->get(route('member.memberships.index'))
            ->assertOk()
            ->assertSee('Ready to join');
        $this->get(route('member.membership-requests.index'))
            ->assertOk()
            ->assertSee('Ready to join');

        $staff = $this->makeUser('Staff', 'staff@example.test');
        $this->actingAs($staff)
            ->from(route('staff.membership-plans.edit', $plan))
            ->put(route('staff.membership-plans.update', $plan), [
                'plan_name' => 'Standard',
                'duration_days' => 30,
                'price' => '1100.00',
            ])
            ->assertRedirect(route('staff.membership-plans.edit', $plan))
            ->assertSessionHasErrors('plan_name');
        $this->assertSame('1000.00', $plan->fresh()->price);

        $inactivePlan = $this->makePlan('Unavailable', 'Inactive');
        $this->actingAs($user)
            ->from(route('member.plans.index'))
            ->post(route('member.plans.purchase', $inactivePlan))
            ->assertRedirect(route('member.plans.index'))
            ->assertSessionHasErrors('plan_id');

        $foreignMembership = $this->makeMembership($otherMember, $plan);
        $this->post(route('member.memberships.renew', $foreignMembership), [
            'plan_id' => $plan->plan_id,
        ])->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('member.memberships.index'))
            ->assertOk()
            ->assertDontSee('Ready to join');
    }

    public function test_purchase_approval_records_exact_payment_and_plan_snapshot_atomically(): void
    {
        $reviewer = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Monthly', 'Active', 30, '1250.00');
        $membershipRequest = $this->makeRequest($member, $plan, $memberUser);
        $this->travelTo(Carbon::parse('2026-03-10 09:00:00'));

        $this->actingAs($reviewer)
            ->get(route('admin.membership-requests.review', $membershipRequest))
            ->assertOk()
            ->assertSee('1,250.00');

        $this->post(route('admin.membership-requests.approve', $membershipRequest), [
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('payment_confirmed');

        $this->post(route('admin.membership-requests.approve', $membershipRequest), [
            'payment_confirmed' => '1',
            'payment_method' => 'GCash',
            'reference_number' => 'GC-123',
            'payment_notes' => 'Paid at front desk',
            'amount' => '1.00',
        ])->assertRedirect();

        $createdMembership = Membership::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();
        $this->assertSame('Monthly', $createdMembership->plan_name_snapshot);
        $this->assertSame('1250.00', $createdMembership->plan_price_snapshot);
        $this->assertSame(30, $createdMembership->plan_duration_snapshot);
        $this->assertSame('2026-03-10', $createdMembership->start_date->toDateString());
        $this->assertSame('2026-04-08', $createdMembership->end_date->toDateString());
        $this->assertSame('Active', $createdMembership->membership_status);
        $this->assertSame('1250.00', $payment->amount);
        $this->assertSame('GCash', $payment->payment_method);
        $this->assertSame('GC-123', $payment->reference_number);
        $this->assertSame($reviewer->user_id, $payment->received_by);
        $this->assertSame('Approved', $membershipRequest->fresh()->request_status);
        $this->assertSame($createdMembership->membership_id, $membershipRequest->fresh()->membership_id);
        $this->assertSame($reviewer->user_id, $membershipRequest->fresh()->reviewed_by);
        $this->get(route('admin.memberships.show', $createdMembership))
            ->assertOk()
            ->assertSee('Payments')
            ->assertSee('Membership Requests');
    }

    public function test_member_can_request_one_renewal_for_their_current_membership(): void
    {
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Renewal Plan');
        $startDate = today()->copy()->subDays(10)->toDateString();
        $endDate = today()->copy()->addDays(10)->toDateString();
        $membership = $this->makeMembership($member, $plan, $startDate, $endDate);

        $this->actingAs($memberUser)
            ->post(route('member.memberships.renew', $membership), [
                'plan_id' => $plan->plan_id,
                'request_reason' => 'Continue training',
            ])
            ->assertRedirect(route('member.memberships.index'));

        $renewalRequest = MembershipRequest::query()->where('request_type', 'Renewal')->firstOrFail();
        $this->assertSame($membership->membership_id, $renewalRequest->membership_id);
        $this->assertSame('Continue training', $renewalRequest->request_reason);

        $this->from(route('member.memberships.index'))
            ->post(route('member.memberships.renew', $membership), [
                'plan_id' => $plan->plan_id,
            ])
            ->assertRedirect(route('member.memberships.index'))
            ->assertSessionHasErrors('membership_id');
    }

    public function test_membership_lists_derive_expired_and_upcoming_statuses_from_dates(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $member = $this->makeMember($this->makeUser('Member', 'member@example.test'));
        $expiredPlan = $this->makePlan('Expired Plan');
        $upcomingPlan = $this->makePlan('Upcoming Plan');
        $this->makeMembership($member, $expiredPlan, '2026-02-01', '2026-03-09');
        $this->makeMembership($member, $upcomingPlan, '2026-03-11', '2026-04-09');
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));

        $this->actingAs($admin)
            ->get(route('admin.memberships.index'))
            ->assertOk()
            ->assertSee('Expired Plan')
            ->assertSee('Upcoming Plan')
            ->assertSee('Upcoming');

        $this->get(route('admin.memberships.index', ['status' => 'Expired']))
            ->assertOk()
            ->assertSee('Expired Plan')
            ->assertDontSee('Upcoming Plan');

        $this->get(route('admin.memberships.index', ['status' => 'Upcoming']))
            ->assertOk()
            ->assertSee('Upcoming Plan')
            ->assertDontSee('Expired Plan');
    }

    public function test_approval_rechecks_plan_availability(): void
    {
        $reviewer = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Temporarily Active');
        $membershipRequest = $this->makeRequest($member, $plan, $memberUser);
        $plan->plan_status = 'Inactive';
        $plan->save();

        $this->actingAs($reviewer)
            ->from(route('admin.membership-requests.review', $membershipRequest))
            ->post(route('admin.membership-requests.approve', $membershipRequest), [
                'payment_confirmed' => '1',
                'payment_method' => 'Cash',
            ])
            ->assertRedirect(route('admin.membership-requests.review', $membershipRequest))
            ->assertSessionHasErrors('plan_id');

        $this->assertSame('Pending', $membershipRequest->fresh()->request_status);
        $this->assertDatabaseCount('memberships', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_early_renewal_starts_after_current_expiry_and_keeps_old_plan_snapshot(): void
    {
        $reviewer = $this->makeUser('Staff', 'staff@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $oldPlan = $this->makePlan('Old Plan', 'Inactive', 30, '1000.00');
        $newPlan = $this->makePlan('New Plan', 'Active', 14, '700.00');
        $oldMembership = $this->makeMembership(
            $member,
            $oldPlan,
            '2026-02-01',
            '2026-03-15',
            'Old Plan Snapshot',
            '900.00',
            28,
        );
        $membershipRequest = $this->makeRequest(
            $member,
            $newPlan,
            $memberUser,
            'Renewal',
            $oldMembership,
        );
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));

        $this->actingAs($reviewer)
            ->post(route('staff.membership-requests.approve', $membershipRequest), [
                'payment_confirmed' => '1',
                'payment_method' => 'Card',
            ])
            ->assertRedirect();

        $newMembership = Membership::query()->where('membership_id', '!=', $oldMembership->membership_id)->firstOrFail();
        $this->assertSame('2026-03-16', $newMembership->start_date->toDateString());
        $this->assertSame('2026-03-29', $newMembership->end_date->toDateString());
        $this->assertSame('New Plan', $newMembership->plan_name_snapshot);
        $this->assertSame('700.00', $newMembership->plan_price_snapshot);
        $this->assertSame('Old Plan Snapshot', $oldMembership->fresh()->plan_name_snapshot);
        $this->assertSame('Active', $oldMembership->fresh()->membership_status);
    }

    public function test_expired_renewal_starts_on_payment_date(): void
    {
        $reviewer = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Renewal', 'Active', 7, '300.00');
        $expiredMembership = $this->makeMembership(
            $member,
            $plan,
            '2026-01-01',
            '2026-01-07',
            status: 'Expired',
        );
        $membershipRequest = $this->makeRequest($member, $plan, $memberUser, 'Renewal', $expiredMembership);
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));

        $this->actingAs($reviewer)
            ->post(route('admin.membership-requests.approve', $membershipRequest), [
                'payment_confirmed' => '1',
                'payment_method' => 'Cash',
            ])
            ->assertRedirect();

        $newMembership = Membership::query()->where('membership_id', '!=', $expiredMembership->membership_id)->firstOrFail();
        $this->assertSame('2026-03-10', $newMembership->start_date->toDateString());
        $this->assertSame('2026-03-16', $newMembership->end_date->toDateString());
    }

    public function test_staff_can_assign_paid_membership_on_behalf_of_member(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Staff Plan', 'Active', 30, '999.00');

        $this->actingAs($staff)
            ->get(route('staff.memberships.index'))
            ->assertOk()
            ->assertSee('Memberships');
        $this->get(route('staff.memberships.assign.create'))
            ->assertOk()
            ->assertSee('Assign Paid Membership');

        $this->post(route('staff.memberships.assign'), [
            'member_id' => $member->member_id,
            'plan_id' => $plan->plan_id,
            'payment_method' => 'Other',
        ])->assertSessionHasErrors('payment_confirmed');

        $this->post(route('staff.memberships.assign'), [
            'member_id' => $member->member_id,
            'plan_id' => $plan->plan_id,
            'payment_confirmed' => '1',
            'payment_method' => 'Other',
        ])->assertRedirect();

        $this->assertDatabaseCount('memberships', 1);
        $this->assertSame('999.00', Payment::query()->firstOrFail()->amount);
        $this->get(route('staff.memberships.index'))
            ->assertOk()
            ->assertSee('Staff Plan');
    }

    public function test_rejecting_request_does_not_create_membership_or_payment(): void
    {
        $reviewer = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Monthly');
        $membershipRequest = $this->makeRequest($member, $plan, $memberUser);

        $this->actingAs($reviewer)
            ->post(route('admin.membership-requests.reject', $membershipRequest), [
                'decision_reason' => 'Please update your application.',
            ])
            ->assertRedirect(route('admin.membership-requests.index'));

        $this->assertSame('Rejected', $membershipRequest->fresh()->request_status);
        $this->assertSame('Please update your application.', $membershipRequest->fresh()->decision_reason);
        $this->assertDatabaseCount('memberships', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->get(route('admin.membership-requests.index'))
            ->assertOk()
            ->assertSee('Rejected');
    }

    public function test_payment_failure_rolls_back_membership_and_request_approval(): void
    {
        $reviewer = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Monthly');
        $membershipRequest = $this->makeRequest($member, $plan, $memberUser);
        $this->actingAs($reviewer)->withoutExceptionHandling();

        Payment::creating(function (): void {
            throw new RuntimeException('Simulated payment write failure.');
        });

        try {
            $this->post(route('admin.membership-requests.approve', $membershipRequest), [
                'payment_confirmed' => '1',
                'payment_method' => 'Cash',
            ]);
            $this->fail('The simulated payment failure should have been thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated payment write failure.', $exception->getMessage());
        }

        $this->assertSame('Pending', $membershipRequest->fresh()->request_status);
        $this->assertDatabaseCount('memberships', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_guests_and_members_cannot_review_requests_or_directly_assign_memberships(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $plan = $this->makePlan('Monthly');
        $membershipRequest = $this->makeRequest($member, $plan, $memberUser);

        $this->get(route('admin.membership-requests.index'))->assertRedirect('/login');
        $this->actingAs($memberUser)
            ->get(route('admin.membership-requests.index'))
            ->assertForbidden();
        $this->post(route('admin.membership-requests.approve', $membershipRequest), [
            'payment_confirmed' => '1',
            'payment_method' => 'Cash',
        ])->assertForbidden();
        $this->actingAs($admin)
            ->get(route('admin.membership-requests.review', $membershipRequest))
            ->assertOk();
    }

    public function test_membership_routes_have_no_delete_endpoint(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (str_contains($route->uri(), 'membership')) {
                $this->assertNotContains('DELETE', $route->methods());
            }
        }
    }

    private function makeUser(string $role, string $email, string $status = 'Active'): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $email,
            'password' => Hash::make('test-password-123'),
            'role' => $role,
            'account_status' => $status,
        ]);
    }

    private function makeMember(User $user, string $status = 'Active'): Member
    {
        return Member::create([
            'user_id' => $user->user_id,
            'first_name' => 'Member',
            'last_name' => $user->last_name,
            'phone' => '555-0000',
            'member_status' => $status,
        ]);
    }

    private function makePlan(
        string $name,
        string $status = 'Active',
        int $duration = 30,
        string $price = '1000.00',
    ): MembershipPlan {
        return MembershipPlan::create([
            'plan_name' => $name,
            'duration_days' => $duration,
            'price' => $price,
            'plan_status' => $status,
        ]);
    }

    private function makeMembership(
        Member $member,
        MembershipPlan $plan,
        string $startDate = '2026-01-01',
        string $endDate = '2026-01-30',
        ?string $snapshotName = null,
        ?string $snapshotPrice = null,
        ?int $snapshotDuration = null,
        string $status = 'Active',
    ): Membership {
        return Membership::create([
            'member_id' => $member->member_id,
            'plan_id' => $plan->plan_id,
            'plan_name_snapshot' => $snapshotName ?? $plan->plan_name,
            'plan_price_snapshot' => $snapshotPrice ?? $plan->price,
            'plan_duration_snapshot' => $snapshotDuration ?? $plan->duration_days,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'membership_status' => $status,
        ]);
    }

    private function makeRequest(
        Member $member,
        MembershipPlan $plan,
        User $requester,
        string $type = 'Purchase',
        ?Membership $membership = null,
    ): MembershipRequest {
        return MembershipRequest::create([
            'member_id' => $member->member_id,
            'membership_id' => $membership?->membership_id,
            'plan_id' => $plan->plan_id,
            'requested_by' => $requester->user_id,
            'request_type' => $type,
            'request_status' => 'Pending',
            'requested_at' => now(),
        ]);
    }
}
