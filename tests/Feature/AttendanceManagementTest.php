<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\OperatingSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as BaseTestCase;

class AttendanceManagementTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('operating_schedules');
        Schema::dropIfExists('system_settings');
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

        Schema::create('attendance', function (Blueprint $table): void {
            $table->bigIncrements('attendance_id');
            $table->unsignedBigInteger('member_id');
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->string('attendance_source', 30);
            $table->timestamps();
            $table->foreign('member_id')->references('member_id')->on('members');
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->bigIncrements('correction_id');
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('corrected_by');
            $table->dateTime('original_check_in_at');
            $table->dateTime('corrected_check_in_at');
            $table->dateTime('original_check_out_at')->nullable();
            $table->dateTime('corrected_check_out_at')->nullable();
            $table->text('correction_reason');
            $table->dateTime('corrected_at');
            $table->foreign('attendance_id')->references('attendance_id')->on('attendance');
            $table->foreign('corrected_by')->references('user_id')->on('users');
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->bigIncrements('setting_id');
            $table->string('setting_key', 120)->unique();
            $table->text('setting_value')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('updated_by')->references('user_id')->on('users');
        });

        Schema::create('operating_schedules', function (Blueprint $table): void {
            $table->bigIncrements('schedule_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opening_time');
            $table->time('closing_time');
            $table->boolean('is_open')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('updated_by')->references('user_id')->on('users');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_member_can_check_in_and_out_and_only_sees_own_history(): void
    {
        Carbon::setTestNow('2026-04-15 09:00:00');
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = $this->makeMember($memberUser);
        $otherMember = $this->makeMember($this->makeUser('Member', 'other@example.test'));
        $otherAttendance = $this->makeAttendance($otherMember);

        $this->actingAs($memberUser)
            ->get(route('member.attendance.index'))
            ->assertOk()
            ->assertSee('Check in')
            ->assertDontSee('other@example.test');

        $this->post(route('member.attendance.check-in'))
            ->assertRedirect(route('member.attendance.index'));

        $attendance = Attendance::query()->where('member_id', $member->member_id)->firstOrFail();
        $this->assertSame('Member self-service', $attendance->attendance_source);
        $this->assertNull($attendance->check_out_at);

        $this->from(route('member.attendance.index'))
            ->post(route('member.attendance.check-in'))
            ->assertRedirect(route('member.attendance.index'))
            ->assertSessionHasErrors('attendance');

        $this->post(route('member.attendance.check-out', $otherAttendance))->assertNotFound();

        Carbon::setTestNow('2026-04-15 09:05:00');
        $this->post(route('member.attendance.check-out', $attendance))
            ->assertRedirect(route('member.attendance.index'));

        $this->assertNotNull($attendance->fresh()->check_out_at);
        $this->get(route('member.attendance.index'))
            ->assertOk()
            ->assertSee('Member self-service');
        $this->get('/member/attendance/'.$attendance->attendance_id.'/edit')->assertNotFound();
    }

    public function test_check_in_validates_account_and_membership_and_does_not_block_at_capacity(): void
    {
        Carbon::setTestNow('2026-04-15 09:00:00');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $firstMember = $this->makeMember($this->makeUser('Member', 'first@example.test'));
        $secondMemberUser = $this->makeUser('Member', 'second@example.test');
        $secondMember = $this->makeMember($secondMemberUser);
        $this->makeMember($this->makeUser('Member', 'inactive@example.test'), 'Inactive');

        $this->actingAs($staff)
            ->get(route('staff.attendance.check-in.create'))
            ->assertOk()
            ->assertSee('Staff-assisted check-in');

        $this->actingAs($staff)
            ->post(route('staff.attendance.check-in'), ['member_id' => $firstMember->member_id])
            ->assertRedirect(route('staff.attendance.index'));
        $staffAttendance = Attendance::query()->where('member_id', $firstMember->member_id)->firstOrFail();
        $this->assertSame('Staff-assisted', $staffAttendance->attendance_source);

        $this->actingAs($admin)
            ->put(route('admin.attendance.capacity.update'), ['maximum_capacity' => 1])
            ->assertRedirect(route('admin.attendance.index'));

        $this->actingAs($staff)
            ->get(route('staff.attendance.occupancy'))
            ->assertOk()
            ->assertJson([
                'occupancy' => 1,
                'maximum_capacity' => 1,
                'at_capacity' => true,
            ]);

        $this->actingAs($secondMemberUser)
            ->post(route('member.attendance.check-in'))
            ->assertRedirect(route('member.attendance.index'));

        $this->assertDatabaseCount('attendance', 2);
        $this->actingAs($staff)
            ->get(route('staff.attendance.occupancy'))
            ->assertJson([
                'occupancy' => 2,
                'maximum_capacity' => 1,
                'at_capacity' => true,
            ]);

        $this->post(route('staff.attendance.check-out', $staffAttendance))
            ->assertRedirect(route('staff.attendance.index'));
        $this->assertNotNull($staffAttendance->fresh()->check_out_at);

        $inactive = Member::query()->where('member_status', 'Inactive')->firstOrFail();
        $this->post(route('staff.attendance.check-in'), ['member_id' => $inactive->member_id])
            ->assertSessionHasErrors('member_id');

        $inactiveAccount = $this->makeMember(
            $this->makeUser('Member', 'inactive-account@example.test'),
            accountStatus: 'Inactive',
        );
        $this->post(route('staff.attendance.check-in'), ['member_id' => $inactiveAccount->member_id])
            ->assertSessionHasErrors('member_id');

        $expiredMembership = $this->makeMember(
            $this->makeUser('Member', 'expired@example.test'),
            endDate: today()->subDay()->toDateString(),
        );
        $this->post(route('staff.attendance.check-in'), ['member_id' => $expiredMembership->member_id])
            ->assertSessionHasErrors('member_id');

        $inactiveMembership = $this->makeMember(
            $this->makeUser('Member', 'inactive-membership@example.test'),
            membershipStatus: 'Inactive',
        );
        $this->post(route('staff.attendance.check-in'), ['member_id' => $inactiveMembership->member_id])
            ->assertSessionHasErrors('member_id');

        $this->actingAs($this->makeUser('Admin', 'invalid-capacity@example.test'));
        $this->put(route('admin.attendance.capacity.update'), ['maximum_capacity' => 0])
            ->assertSessionHasErrors('maximum_capacity');

        $this->actingAs($staff)
            ->put(route('admin.attendance.capacity.update'), ['maximum_capacity' => 10])
            ->assertForbidden();
    }

    public function test_admin_and_staff_corrections_preserve_original_values_with_reason(): void
    {
        Carbon::setTestNow('2026-04-15 12:00:00');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $member = $this->makeMember($this->makeUser('Member', 'member@example.test'));
        $attendance = $this->makeAttendance(
            $member,
            checkInAt: Carbon::parse('2026-04-15 08:00:00'),
            checkOutAt: Carbon::parse('2026-04-15 11:00:00'),
        );

        $this->actingAs($staff)
            ->get(route('staff.attendance.edit', $attendance))
            ->assertOk()
            ->assertSee('Correction history');

        $this->put(route('staff.attendance.update', $attendance), [
            'check_in_at' => '2026-04-15T08:10',
            'check_out_at' => '2026-04-15T11:05',
            'correction_reason' => 'Corrected from the sign-in log',
        ])->assertRedirect(route('staff.attendance.index'));

        $this->assertSame('2026-04-15 08:10:00', $attendance->fresh()->check_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-15 11:05:00', $attendance->fresh()->check_out_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->attendance_id,
            'corrected_by' => $staff->user_id,
            'original_check_in_at' => '2026-04-15 08:00:00',
            'corrected_check_in_at' => '2026-04-15 08:10:00',
            'original_check_out_at' => '2026-04-15 11:00:00',
            'corrected_check_out_at' => '2026-04-15 11:05:00',
            'correction_reason' => 'Corrected from the sign-in log',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['search' => 'member@example.test', 'status' => 'closed']))
            ->assertOk()
            ->assertSee('member@example.test')
            ->assertSee('setInterval(refreshOccupancy, 5000)');

        $this->put(route('admin.attendance.update', $attendance), [
            'check_in_at' => '2026-04-15T10:00',
            'check_out_at' => '2026-04-15T09:00',
            'correction_reason' => 'Invalid time order',
        ])->assertSessionHasErrors('check_out_at');

        $this->assertDatabaseCount('attendance_corrections', 1);

        $this->makeAttendance($member, checkInAt: Carbon::parse('2026-04-15 11:30:00'));
        $this->put(route('admin.attendance.update', $attendance), [
            'check_in_at' => '2026-04-15T08:10',
            'check_out_at' => '',
            'correction_reason' => 'Do not create a second open session',
        ])->assertSessionHasErrors('check_out_at');
        $this->assertDatabaseCount('attendance_corrections', 1);
    }

    public function test_auto_close_uses_configured_closing_time(): void
    {
        Carbon::setTestNow('2026-04-15 17:30:00');
        $member = $this->makeMember($this->makeUser('Member', 'member@example.test'));
        $attendance = $this->makeAttendance($member, checkInAt: now());
        OperatingSchedule::query()->create([
            'day_of_week' => now()->dayOfWeekIso,
            'opening_time' => '06:00:00',
            'closing_time' => '18:00:00',
            'is_open' => true,
        ]);

        Carbon::setTestNow('2026-04-15 18:01:00');
        $this->artisan('attendance:auto-close')
            ->expectsOutput('Closed 1 attendance session(s).')
            ->assertSuccessful();

        $this->assertSame('2026-04-15 18:00:00', $attendance->fresh()->check_out_at->format('Y-m-d H:i:s'));
        $this->artisan('attendance:auto-close')
            ->expectsOutput('Closed 0 attendance session(s).')
            ->assertSuccessful();
    }

    private function makeUser(string $role, string $email, string $status = 'Active'): User
    {
        return User::query()->create([
            'first_name' => ucfirst($role),
            'last_name' => 'Test',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'account_status' => $status,
        ]);
    }

    private function makeMember(
        User $user,
        string $memberStatus = 'Active',
        string $accountStatus = 'Active',
        string $membershipStatus = 'Active',
        ?string $startDate = null,
        ?string $endDate = null,
    ): Member {
        if ($user->account_status !== $accountStatus) {
            $user->account_status = $accountStatus;
            $user->save();
        }

        $member = Member::query()->create([
            'user_id' => $user->user_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => '555-0100',
            'member_status' => $memberStatus,
        ]);
        $plan = MembershipPlan::query()->create([
            'plan_name' => 'Plan '.$member->member_id,
            'description' => null,
            'duration_days' => 30,
            'price' => '100.00',
            'plan_status' => 'Active',
        ]);

        Membership::query()->create([
            'member_id' => $member->member_id,
            'plan_id' => $plan->plan_id,
            'plan_name_snapshot' => $plan->plan_name,
            'plan_price_snapshot' => $plan->price,
            'plan_duration_snapshot' => $plan->duration_days,
            'start_date' => $startDate ?? today()->subDays(1)->toDateString(),
            'end_date' => $endDate ?? today()->addDays(30)->toDateString(),
            'membership_status' => $membershipStatus,
        ]);

        return $member;
    }

    private function makeAttendance(
        Member $member,
        ?Carbon $checkInAt = null,
        ?Carbon $checkOutAt = null,
    ): Attendance {
        return Attendance::query()->create([
            'member_id' => $member->member_id,
            'check_in_at' => $checkInAt ?? now()->subHour(),
            'check_out_at' => $checkOutAt,
            'attendance_source' => 'Staff-assisted',
        ]);
    }
}
