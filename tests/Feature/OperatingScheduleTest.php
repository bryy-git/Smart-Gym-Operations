<?php

namespace Tests\Feature;

use App\Models\GymClosure;
use App\Models\OperatingSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperatingScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('gym_closures');
        Schema::dropIfExists('operating_schedules');
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

        Schema::create('gym_closures', function (Blueprint $table): void {
            $table->bigIncrements('closure_id');
            $table->string('title', 180);
            $table->text('reason')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('closure_status', 20);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->foreign('created_by')->references('user_id')->on('users');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_manage_intervals_but_overlaps_and_invalid_times_are_rejected(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');

        $this->actingAs($admin)
            ->post(route('admin.operating-schedules.store'), [
                'day_of_week' => 1,
                'opening_time' => '09:00',
                'closing_time' => '12:00',
            ])
            ->assertRedirect(route('admin.operating-schedules.index'));

        $this->post(route('admin.operating-schedules.store'), [
            'day_of_week' => 1,
            'opening_time' => '11:00',
            'closing_time' => '13:00',
        ])->assertSessionHasErrors('opening_time');

        $this->post(route('admin.operating-schedules.store'), [
            'day_of_week' => 1,
            'opening_time' => '12:00',
            'closing_time' => '14:00',
        ])->assertRedirect(route('admin.operating-schedules.index'));

        $firstSchedule = OperatingSchedule::query()->where('opening_time', '09:00')->firstOrFail();
        $this->put(route('admin.operating-schedules.update', $firstSchedule), [
            'opening_time' => '10:00',
            'closing_time' => '13:00',
            'is_open' => '1',
        ])->assertSessionHasErrors('opening_time');

        $this->post(route('admin.operating-schedules.store'), [
            'day_of_week' => 2,
            'opening_time' => '15:00',
            'closing_time' => '14:00',
        ])->assertSessionHasErrors('closing_time');

        $this->assertDatabaseCount('operating_schedules', 2);

        $this->patch(route('admin.operating-schedules.day.close', 1))
            ->assertRedirect(route('admin.operating-schedules.index'));

        $this->assertSame(0, OperatingSchedule::query()->where('day_of_week', 1)->where('is_open', true)->count());
        $this->get(route('admin.operating-schedules.index'))
            ->assertOk()
            ->assertSee('Monday')
            ->assertSee('Closed');
    }

    public function test_staff_and_members_can_view_hours_and_relevant_closure_announcements(): void
    {
        Carbon::setTestNow('2026-04-15 09:00:00');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $member = $this->makeUser('Member', 'member@example.test');

        $this->actingAs($admin)
            ->post(route('admin.operating-schedules.store'), [
                'day_of_week' => 1,
                'opening_time' => '09:00',
                'closing_time' => '17:00',
            ]);

        $this->actingAs($admin)
            ->post(route('admin.gym-closures.store'), [
                'title' => 'Planned maintenance',
                'reason' => 'The gym floor is being repaired.',
                'start_datetime' => '2026-04-20T10:00',
                'end_datetime' => '2026-04-20T12:00',
            ])
            ->assertRedirect(route('admin.gym-closures.index'));

        $this->actingAs($admin)
            ->post(route('admin.gym-closures.store'), [
                'title' => 'Current closure',
                'start_datetime' => '2026-04-15T08:00',
                'end_datetime' => '2026-04-15T10:00',
            ])
            ->assertRedirect(route('admin.gym-closures.index'));

        $this->assertDatabaseHas('gym_closures', [
            'title' => 'Planned maintenance',
            'closure_status' => 'Scheduled',
        ]);
        $this->assertDatabaseHas('gym_closures', [
            'title' => 'Current closure',
            'closure_status' => 'Active',
        ]);
        GymClosure::query()->create([
            'title' => 'Past closure',
            'reason' => null,
            'start_datetime' => '2026-04-14 08:00:00',
            'end_datetime' => '2026-04-14 10:00:00',
            'closure_status' => 'Completed',
            'created_by' => $admin->user_id,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.operating-schedules.index'))
            ->assertOk()
            ->assertSee('09:00')
            ->assertSee('Planned maintenance')
            ->assertSee('Current closure')
            ->assertDontSee('Past closure');

        $this->actingAs($member)
            ->get(route('member.operating-schedules.index'))
            ->assertOk()
            ->assertSee('Planned maintenance')
            ->assertSee('Current closure')
            ->assertDontSee('Past closure');

        $this->actingAs($member)
            ->post(route('admin.gym-closures.store'), [
                'title' => 'Unauthorized',
                'start_datetime' => '2026-04-20T10:00',
                'end_datetime' => '2026-04-20T12:00',
            ])
            ->assertForbidden();

        $this->assertSame(3, GymClosure::query()->count());
    }

    public function test_admin_closure_forms_validate_date_order_and_allow_editing(): void
    {
        Carbon::setTestNow('2026-04-15 09:00:00');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $closure = GymClosure::query()->create([
            'title' => 'Holiday hours',
            'reason' => null,
            'start_datetime' => '2026-04-20 10:00:00',
            'end_datetime' => '2026-04-20 12:00:00',
            'closure_status' => 'Scheduled',
            'created_by' => $admin->user_id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.gym-closures.update', $closure), [
                'title' => 'Invalid period',
                'start_datetime' => '2026-04-20T12:00',
                'end_datetime' => '2026-04-20T10:00',
            ])
            ->assertSessionHasErrors('end_datetime');

        $this->put(route('admin.gym-closures.update', $closure), [
            'title' => 'Holiday closure',
            'start_datetime' => '2026-04-20T10:00',
            'end_datetime' => '2026-04-20T12:00',
        ])->assertRedirect(route('admin.gym-closures.index'));

        $this->assertSame('Holiday closure', $closure->fresh()->title);
        $this->get(route('admin.gym-closures.index'))
            ->assertOk()
            ->assertSee('Scheduled')
            ->assertSee('Holiday closure');
    }

    private function makeUser(string $role, string $email): User
    {
        return User::query()->create([
            'first_name' => ucfirst($role),
            'last_name' => 'Tester',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'account_status' => 'Active',
        ]);
    }
}
