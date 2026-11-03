<?php

namespace Tests\Feature;

use App\Jobs\DeliverNotificationEmail;
use App\Mail\NotificationEmail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('memberships');
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
            $table->string('member_status', 20);
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users');
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
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->bigIncrements('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('notification_type', 80);
            $table->string('title', 180);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at');
            $table->foreign('user_id')->references('user_id')->on('users');
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->bigIncrements('delivery_id');
            $table->unsignedBigInteger('notification_id');
            $table->string('delivery_channel', 20);
            $table->string('delivery_status', 20);
            $table->dateTime('sent_at')->nullable();
            $table->text('error_details')->nullable();
            $table->unique(['notification_id', 'delivery_channel']);
            $table->foreign('notification_id')
                ->references('notification_id')
                ->on('notifications')
                ->cascadeOnDelete();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notification_creation_tracks_in_system_and_email_delivery(): void
    {
        Mail::fake();
        $user = $this->makeUser('Member', 'member@example.test');

        $notification = app(NotificationService::class)->create(
            $user,
            'Announcement',
            'Gym maintenance',
            'The gym will close early today.',
        );

        $this->assertSame(
            ['Email' => 'Sent', 'In-system' => 'Sent'],
            $notification->deliveries()->orderBy('delivery_channel')->pluck('delivery_status', 'delivery_channel')->all(),
        );
        Mail::assertSent(NotificationEmail::class, fn (NotificationEmail $mail): bool => $mail->title === 'Gym maintenance');
    }

    public function test_members_can_manage_only_their_own_notification_history(): void
    {
        Mail::fake();
        $member = $this->makeUser('Member', 'member@example.test');
        $otherMember = $this->makeUser('Member', 'other@example.test');
        $membership = $this->makeMembership($member, today()->toDateString(), today()->addDays(30)->toDateString());
        $notification = app(NotificationService::class)->create($member, 'Announcement', 'Notice', 'Account-specific notice.');
        $otherNotification = app(NotificationService::class)->create($otherMember, 'Announcement', 'Private', 'Other user notice.');

        $this->actingAs($member)
            ->get(route('member.notifications.index'))
            ->assertOk()
            ->assertSee('Account-specific notice.')
            ->assertDontSee('Other user notice.')
            ->assertSee('Email: Sent')
            ->assertSee('In-system: Sent');

        $this->patch(route('member.notifications.read', $notification))
            ->assertRedirect(route('member.notifications.index'));
        $this->assertTrue($notification->fresh()->is_read);

        $this->delete(route('member.notifications.destroy', $otherNotification))
            ->assertNotFound();
        $this->delete(route('member.notifications.destroy', $notification))
            ->assertRedirect(route('member.notifications.index'));

        $this->assertDatabaseMissing('notifications', ['notification_id' => $notification->notification_id]);
        $this->assertDatabaseHas('memberships', ['membership_id' => $membership->membership_id]);
        $this->assertDatabaseHas('notifications', ['notification_id' => $otherNotification->notification_id]);
        $this->assertSame(0, NotificationDelivery::query()->where('notification_id', $notification->notification_id)->count());
    }

    public function test_membership_reminders_are_scheduled_tracked_and_duplicate_suppressed(): void
    {
        Carbon::setTestNow('2026-04-15 08:00:00');
        Mail::fake();
        $sevenDayMember = $this->makeUser('Member', 'seven@example.test');
        $threeDayMember = $this->makeUser('Member', 'three@example.test');
        $this->makeMembership($sevenDayMember, '2026-04-01', '2026-04-22');
        $this->makeMembership($threeDayMember, '2026-04-01', '2026-04-18');

        $this->artisan('notifications:membership-reminders')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $sevenDayMember->user_id,
            'title' => 'Membership expires in 7 days',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $threeDayMember->user_id,
            'title' => 'Membership expires in 3 days',
        ]);
        $this->assertSame(2, NotificationDelivery::query()->where('delivery_channel', 'Email')->where('delivery_status', 'Sent')->count());
        Mail::assertSent(NotificationEmail::class, 2);

        $this->artisan('notifications:membership-reminders')
            ->expectsOutputToContain('suppressed 2 duplicate(s)')
            ->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);

        $this->artisan('schedule:list')->expectsOutputToContain('notifications:membership-reminders')->assertSuccessful();
    }

    public function test_renewal_suppresses_reminders_for_the_previous_membership(): void
    {
        Carbon::setTestNow('2026-04-15 08:00:00');
        Mail::fake();
        $user = $this->makeUser('Member', 'renewed@example.test');
        $this->makeMembership($user, '2026-04-01', '2026-04-18');
        $this->makeMembership($user, '2026-04-19', '2026-05-18');

        $this->artisan('notifications:membership-reminders')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
        Mail::assertNothingSent();
    }

    public function test_queued_reminder_email_is_suppressed_after_renewal(): void
    {
        Carbon::setTestNow('2026-04-15 08:00:00');
        Mail::fake();
        $user = $this->makeUser('Member', 'queued-renewal@example.test');
        $oldMembership = $this->makeMembership($user, '2026-04-01', '2026-04-18');
        $notification = Notification::query()->create([
            'user_id' => $user->user_id,
            'notification_type' => 'Membership Reminder',
            'title' => 'Membership expires in 3 days',
            'message' => 'Reminder message.',
            'is_read' => false,
            'created_at' => now(),
        ]);
        $notification->deliveries()->create([
            'delivery_channel' => 'Email',
            'delivery_status' => 'Pending',
        ]);
        $this->makeMembership($user, '2026-04-19', '2026-05-18');

        (new DeliverNotificationEmail($notification->notification_id, $oldMembership->membership_id))->handle();

        $this->assertDatabaseHas('notification_deliveries', [
            'notification_id' => $notification->notification_id,
            'delivery_channel' => 'Email',
            'delivery_status' => 'Failed',
            'error_details' => 'Not sent because the membership is no longer current.',
        ]);
        Mail::assertNothingSent();
    }

    public function test_email_failure_is_recorded_and_reported(): void
    {
        $user = $this->makeUser('Member', 'failure@example.test');
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('Mail transport unavailable'));

        try {
            app(NotificationService::class)->create($user, 'Announcement', 'Notice', 'Delivery failure test.');
            $this->fail('The mail transport exception should be surfaced.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Mail transport unavailable', $exception->getMessage());
        }

        $this->assertDatabaseHas('notification_deliveries', [
            'delivery_channel' => 'Email',
            'delivery_status' => 'Failed',
        ]);
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

    private function makeMembership(User $user, string $startDate, string $endDate): Membership
    {
        $member = Member::query()->firstOrCreate(
            ['user_id' => $user->user_id],
            ['member_status' => 'Active'],
        );

        return Membership::query()->create([
            'member_id' => $member->member_id,
            'plan_id' => 1,
            'plan_name_snapshot' => 'Standard Plan',
            'plan_price_snapshot' => '50.00',
            'plan_duration_snapshot' => 30,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'membership_status' => 'Active',
        ]);
    }
}
