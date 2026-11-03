<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Equipment;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipRequest;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'notifications',
            'equipment_usage',
            'maintenance_records',
            'equipment',
            'attendance',
            'activity_logs',
            'online_applications',
            'gym_closures',
            'refunds',
            'payments',
            'membership_requests',
            'memberships',
            'membership_plans',
            'members',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

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

        Schema::create('gym_closures', function (Blueprint $table): void {
            $table->bigIncrements('closure_id');
            $table->string('title', 180);
            $table->text('reason')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('closure_status', 20);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('online_applications', function (Blueprint $table): void {
            $table->bigIncrements('application_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 190);
            $table->string('phone', 30);
            $table->date('date_of_birth')->nullable();
            $table->string('email_verification_status', 20);
            $table->string('application_status', 20);
            $table->dateTime('submitted_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->dateTime('expires_at');
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->bigIncrements('activity_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 120);
            $table->string('affected_entity', 100);
            $table->unsignedBigInteger('affected_record_id')->nullable();
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at');
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->bigIncrements('member_id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 30);
            $table->date('date_of_birth')->nullable();
            $table->string('member_status', 20);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('membership_requests', function (Blueprint $table): void {
            $table->bigIncrements('request_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->string('request_type', 30);
            $table->string('request_status', 20);
            $table->text('request_reason')->nullable();
            $table->dateTime('requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();
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
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->bigIncrements('refund_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('requested_by');
            $table->decimal('requested_amount', 10, 2);
            $table->text('reason');
            $table->string('refund_status', 20);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->text('decision_reason')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('attendance', function (Blueprint $table): void {
            $table->bigIncrements('attendance_id');
            $table->unsignedBigInteger('member_id');
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->string('attendance_source', 30);
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table): void {
            $table->bigIncrements('equipment_id');
            $table->string('equipment_name', 150);
            $table->text('description')->nullable();
            $table->string('equipment_status', 30);
            $table->unsignedInteger('usage_threshold')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_records', function (Blueprint $table): void {
            $table->bigIncrements('maintenance_id');
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('reported_by');
            $table->text('issue_description');
            $table->string('maintenance_status', 30);
            $table->text('action_details')->nullable();
            $table->dateTime('reported_at');
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('equipment_usage', function (Blueprint $table): void {
            $table->bigIncrements('usage_id');
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedInteger('usage_count');
            $table->date('usage_date');
            $table->text('notes')->nullable();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->bigIncrements('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('notification_type', 80);
            $table->string('title', 180);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at');
        });
    }

    public function test_admin_dashboard_uses_database_metrics_and_net_revenue(): void
    {
        Carbon::setTestNow('2026-11-03 20:00:00');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->makeUser('Staff', 'staff@example.test');
        $activeUser = $this->makeUser('Member', 'active@example.test');
        $inactiveUser = $this->makeUser('Member', 'inactive@example.test');
        $activeMember = $this->makeMember($activeUser, 'Active', 'Person', 'Active');
        $inactiveMember = $this->makeMember($inactiveUser, 'Inactive', 'Person', 'Inactive');
        $today = today();

        $currentMembership = $this->makeMembership(
            $activeMember,
            'Monthly Plan',
            $today->copy()->subDays(10)->toDateString(),
            $today->copy()->addDays(3)->toDateString(),
        );
        $this->makeMembership(
            $activeMember,
            'Old Plan',
            $today->copy()->subDays(40)->toDateString(),
            $today->copy()->subDay()->toDateString(),
        );

        $currentPayment = $this->makePayment($currentMembership, $admin, '150.00', now());
        $this->makePayment(
            $currentMembership,
            $admin,
            '500.00',
            now()->copy()->startOfMonth()->subDay(),
        );
        $this->makeRefund($currentPayment, $admin, '30.00', 'Approved', now());
        $this->makeRefund(
            $currentPayment,
            $admin,
            '20.00',
            'Approved',
            now()->copy()->startOfMonth()->subDay(),
        );
        $this->makeRefund($currentPayment, $admin, '10.00', 'Pending', now());

        Attendance::create([
            'member_id' => $activeMember->member_id,
            'check_in_at' => now()->copy()->subHours(2),
            'check_out_at' => now()->copy()->subHour(),
            'attendance_source' => 'Staff-assisted',
        ]);
        Attendance::create([
            'member_id' => $activeMember->member_id,
            'check_in_at' => now()->copy()->subMinutes(20),
            'check_out_at' => null,
            'attendance_source' => 'Member self-service',
        ]);
        Attendance::create([
            'member_id' => $inactiveMember->member_id,
            'check_in_at' => now()->copy()->subDay(),
            'check_out_at' => null,
            'attendance_source' => 'Staff-assisted',
        ]);

        Equipment::create([
            'equipment_name' => 'Treadmill A',
            'equipment_status' => 'Under Maintenance',
        ]);
        $availableWithOpenIssue = Equipment::create([
            'equipment_name' => 'Bike B',
            'equipment_status' => 'Available',
        ]);
        Equipment::create([
            'equipment_name' => 'Mat C',
            'equipment_status' => 'Available',
        ]);
        $thresholdEquipment = Equipment::create([
            'equipment_name' => 'Stepper D',
            'equipment_status' => 'Available',
            'usage_threshold' => 10,
        ]);
        DB::table('maintenance_records')->insert([
            'equipment_id' => $availableWithOpenIssue->equipment_id,
            'reported_by' => $admin->user_id,
            'issue_description' => 'Seat adjustment is loose.',
            'maintenance_status' => 'Reported',
            'reported_at' => now(),
        ]);
        DB::table('maintenance_records')->insert([
            'equipment_id' => $thresholdEquipment->equipment_id,
            'reported_by' => $admin->user_id,
            'issue_description' => 'Routine service completed.',
            'maintenance_status' => 'Resolved',
            'reported_at' => now()->copy()->subDays(7),
            'resolved_at' => now()->copy()->subDays(5),
        ]);
        DB::table('equipment_usage')->insert([
            [
                'equipment_id' => $thresholdEquipment->equipment_id,
                'recorded_by' => $admin->user_id,
                'usage_count' => 100,
                'usage_date' => today()->copy()->subDays(6)->toDateString(),
            ],
            [
                'equipment_id' => $thresholdEquipment->equipment_id,
                'recorded_by' => $admin->user_id,
                'usage_count' => 6,
                'usage_date' => today()->copy()->subDays(4)->toDateString(),
            ],
            [
                'equipment_id' => $thresholdEquipment->equipment_id,
                'recorded_by' => $admin->user_id,
                'usage_count' => 5,
                'usage_date' => today()->copy()->subDay()->toDateString(),
            ],
        ]);
        DB::table('online_applications')->insert([
            [
                'first_name' => 'Pending',
                'last_name' => 'Applicant',
                'email' => 'pending-one@example.test',
                'phone' => '555-0191',
                'email_verification_status' => 'Verified',
                'application_status' => 'Pending',
                'submitted_at' => now()->copy()->subDay(),
                'expires_at' => now()->copy()->addDays(29),
            ],
            [
                'first_name' => 'Other',
                'last_name' => 'Applicant',
                'email' => 'pending-two@example.test',
                'phone' => '555-0192',
                'email_verification_status' => 'Unverified',
                'application_status' => 'Pending',
                'submitted_at' => now(),
                'expires_at' => now()->copy()->addDays(30),
            ],
        ]);
        DB::table('notifications')->insert([
            'user_id' => $admin->user_id,
            'notification_type' => 'System',
            'title' => 'Dashboard test notification',
            'message' => 'Unread dashboard notification.',
            'is_read' => false,
            'created_at' => now(),
        ]);
        DB::table('activity_logs')->insert([
            'user_id' => $admin->user_id,
            'action' => 'Payment recorded',
            'affected_entity' => 'Payment',
            'affected_record_id' => $currentPayment->payment_id,
            'description' => 'A test payment was recorded.',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('totalMembers', 2)
            ->assertViewHas('activeMembers', 1)
            ->assertViewHas('inactiveMemberCount', 1)
            ->assertViewHas('activeMemberships', 1)
            ->assertViewHas('equipmentAlertCount', 3)
            ->assertViewHas('equipmentAlerts', function ($alerts): bool {
                $equipment = $alerts->firstWhere('equipment_name', 'Stepper D');

                return $equipment !== null && (int) $equipment->usage_since_maintenance === 11;
            })
            ->assertViewHas('expiringMembershipCount', 1)
            ->assertViewHas('netRevenue', 120.0)
            ->assertViewHas('pendingApplicationCount', 2)
            ->assertViewHas('pendingPayments', null)
            ->assertViewHas('unreadNotificationCount', 1)
            ->assertViewHas('activities', fn ($activities): bool => $activities->count() === 1
                && $activities->first()->user?->email === 'admin@example.test')
            ->assertViewHas('attendance', fn (array $attendance): bool => $attendance['checkInsToday'] === 2
                && $attendance['currentlyInGym'] === 2)
            ->assertViewHas('attendanceChart', function (array $chart) use ($today): bool {
                $byDate = collect($chart)->keyBy('period');

                return count($chart) === 7
                    && $byDate->get($today->format('M j'))['value'] === 2
                    && $byDate->get($today->copy()->subDay()->format('M j'))['value'] === 1;
            })
            ->assertViewHas('revenueChart', function (array $chart): bool {
                $byMonth = collect($chart)->keyBy('period');

                return count($chart) === 6
                    && $byMonth->get(now()->format('F Y'))['value'] === 150.0
                    && $byMonth->get(now()->copy()->subMonth()->format('F Y'))['value'] === 500.0;
            })
            ->assertSee('Total members')
            ->assertSee('2')
            ->assertSee('Active memberships')
            ->assertSee('Revenue overview')
            ->assertSee('Last 7 days')
            ->assertSee('Last 6 months')
            ->assertSee('Good evening, Test.')
            ->assertSee('Pending payments')
            ->assertSee('Not tracked')
            ->assertSee('₱')
            ->assertSee('Pending online applications')
            ->assertSee('Review page unavailable')
            ->assertSee('Recent activities')
            ->assertSee('A test payment was recorded.')
            ->assertSee('Add member')
            ->assertSee('Record payment')
            ->assertSee('Add equipment')
            ->assertSee('Expiring memberships')
            ->assertSee('Inactive Person')
            ->assertSee('Treadmill A')
            ->assertSee('Bike B')
            ->assertSee('Stepper D')
            ->assertSee('usage 11 / threshold 10')
            ->assertSee('Revenue this month')
            ->assertSee('₱150.00')
            ->assertSee('₱30.00')
            ->assertSee('₱120.00');
    }

    public function test_admin_dashboard_handles_empty_data_without_inventing_pending_payments(): void
    {
        Carbon::setTestNow('2026-11-03 09:00:00');
        $admin = $this->makeUser('Admin', 'empty-admin@example.test');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('pendingPayments', null)
            ->assertViewHas('pendingApplicationCount', 0)
            ->assertViewHas('activities', fn ($activities): bool => $activities->isEmpty())
            ->assertSee('Good morning, Test.')
            ->assertSee('No activities have been recorded yet.')
            ->assertSee('No check-ins recorded in this period.')
            ->assertSee('No payment records in this period.')
            ->assertSee('Payment records have no pending status');
    }

    public function test_sidebar_opens_the_active_navigation_group_for_admin(): void
    {
        $admin = $this->makeUser('Admin', 'navigation-admin@example.test');

        $response = $this->actingAs($admin)
            ->get('/admin/membership-plans')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Membership plans')
            ->assertSee('images/logo.png')
            ->assertSee('class="mobile-sidebar-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="mobile-navigation"', false)
            ->assertSee('class="hamburger-icon"', false)
            ->assertSee('class="mobile-sidebar" id="mobile-navigation" hidden', false)
            ->assertSee('aria-current="page"', false);

        $this->assertSame(
            2,
            preg_match_all(
                '/<details\b(?=[^>]*data-sidebar-group\b)(?=[^>]*\bopen\b)[^>]*>/',
                $response->getContent(),
            ),
        );

        $content = $response->getContent();
        $headerEnd = strpos($content, '</header>');
        $mobileNavigation = strpos($content, '<div class="mobile-sidebar" id="mobile-navigation" hidden>');
        $appShell = strpos($content, '<div class="app-shell">');

        $this->assertNotFalse($headerEnd);
        $this->assertNotFalse($mobileNavigation);
        $this->assertNotFalse($appShell);
        $this->assertLessThan($mobileNavigation, $headerEnd);
        $this->assertLessThan($appShell, $mobileNavigation);
    }

    public function test_staff_dashboard_is_operational_and_does_not_expose_revenue(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/staff/dashboard')->assertRedirect('/login');
        $this->get('/member/dashboard')->assertRedirect('/login');

        $admin = $this->makeUser('Admin', 'admin@example.test');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser, 'Casey', 'Member', 'Active');
        DB::table('gym_closures')->insert([
            'title' => 'Staff dashboard closure notice',
            'reason' => 'A scheduled maintenance closure.',
            'start_datetime' => now()->copy()->addDay(),
            'end_datetime' => now()->copy()->addDays(2),
            'closure_status' => 'Scheduled',
            'created_by' => $admin->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        MembershipRequest::create([
            'member_id' => $member->member_id,
            'requested_by' => $memberUser->user_id,
            'request_type' => 'Renewal',
            'request_status' => 'Pending',
            'requested_at' => now(),
        ]);
        Attendance::create([
            'member_id' => $member->member_id,
            'check_in_at' => now(),
            'check_out_at' => null,
            'attendance_source' => 'Staff-assisted',
        ]);
        Equipment::create([
            'equipment_name' => 'Rower',
            'equipment_status' => 'Unavailable',
        ]);

        $this->actingAs($staff)
            ->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('Check-ins today')
            ->assertSee('Currently in gym')
            ->assertSee('Pending membership requests')
            ->assertSee('Renewal')
            ->assertSee('Rower')
            ->assertSee('Casey Member')
            ->assertSee('Staff dashboard closure notice')
            ->assertSee('Operating schedule')
            ->assertDontSee('Gym closures')
            ->assertDontSee('Revenue')
            ->assertDontSee('Net revenue');

        $this->actingAs($staff)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/staff/dashboard')->assertForbidden();
    }

    public function test_member_dashboard_only_shows_the_authenticated_members_data(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $otherUser = $this->makeUser('Member', 'other@example.test');
        $member = $this->makeMember($memberUser, 'Taylor', 'Member', 'Active');
        $otherMember = $this->makeMember($otherUser, 'Other', 'Person', 'Active');
        DB::table('gym_closures')->insert([
            'title' => 'Member dashboard closure notice',
            'reason' => 'A scheduled maintenance closure.',
            'start_datetime' => now()->copy()->addDay(),
            'end_datetime' => now()->copy()->addDays(2),
            'closure_status' => 'Scheduled',
            'created_by' => $admin->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $membership = $this->makeMembership(
            $member,
            'Foundations Plan',
            today()->copy()->subDays(4)->toDateString(),
            today()->copy()->addDays(26)->toDateString(),
        );
        $this->makePayment($membership, $admin, '75.00', now()->copy()->subDay());
        $otherMembership = $this->makeMembership(
            $otherMember,
            'Private Plan',
            today()->copy()->subDays(4)->toDateString(),
            today()->copy()->addDays(26)->toDateString(),
        );
        $this->makePayment($otherMembership, $admin, '999.00', now());
        Attendance::create([
            'member_id' => $member->member_id,
            'check_in_at' => now()->copy()->subDay(),
            'check_out_at' => now()->copy()->subHours(20),
            'attendance_source' => 'Member self-service',
        ]);
        DB::table('notifications')->insert([
            [
                'user_id' => $memberUser->user_id,
                'notification_type' => 'Announcement',
                'title' => 'Holiday Hours',
                'message' => 'The gym closes early on Friday.',
                'is_read' => false,
                'created_at' => now(),
            ],
            [
                'user_id' => $otherUser->user_id,
                'notification_type' => 'Announcement',
                'title' => 'Other Member Private',
                'message' => 'Private information.',
                'is_read' => false,
                'created_at' => now(),
            ],
        ]);

        $this->actingAs($memberUser)
            ->get('/member/dashboard')
            ->assertOk()
            ->assertSee('Foundations Plan')
            ->assertSee('Visits in the last 30 days')
            ->assertSee('75.00')
            ->assertSee('Holiday Hours')
            ->assertSee('Member dashboard closure notice')
            ->assertViewHas('attendanceCount', 1)
            ->assertViewHas('paymentTotal', 75.0)
            ->assertViewHas('unreadNotificationCount', 1)
            ->assertDontSee('Other Member Private')
            ->assertDontSee('999.00');

        $this->actingAs($admin)->get('/member/dashboard')->assertForbidden();
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $email,
            'password' => Hash::make('dashboard-test-password'),
            'role' => $role,
            'account_status' => 'Active',
        ]);
    }

    private function makeMember(User $user, string $firstName, string $lastName, string $status): Member
    {
        return Member::create([
            'user_id' => $user->user_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '555-0100',
            'member_status' => $status,
        ]);
    }

    private function makeMembership(
        Member $member,
        string $planName,
        string $startDate,
        string $endDate,
        string $status = 'Active',
    ): Membership {
        return Membership::create([
            'member_id' => $member->member_id,
            'plan_id' => 1,
            'plan_name_snapshot' => $planName,
            'plan_price_snapshot' => '100.00',
            'plan_duration_snapshot' => 30,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'membership_status' => $status,
        ]);
    }

    private function makePayment(Membership $membership, User $receiver, string $amount, Carbon $date): Payment
    {
        return Payment::create([
            'membership_id' => $membership->membership_id,
            'amount' => $amount,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'received_by' => $receiver->user_id,
        ]);
    }

    private function makeRefund(Payment $payment, User $reviewer, string $amount, string $status, Carbon $date): Refund
    {
        return Refund::create([
            'payment_id' => $payment->payment_id,
            'requested_by' => $reviewer->user_id,
            'requested_amount' => $amount,
            'reason' => 'Test refund record',
            'refund_status' => $status,
            'reviewed_by' => $status === 'Approved' ? $reviewer->user_id : null,
            'reviewed_at' => $date,
            'approved_amount' => $status === 'Approved' ? $amount : null,
        ]);
    }
}
