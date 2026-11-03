<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.password_reset_cache_store' => 'array']);
        Cache::store('array')->flush();

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
    }

    public function test_login_regenerates_session_and_redirects_by_role(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->get('/login')->assertOk()->assertSee('Log in');
        $sessionId = $this->app['session']->getId();

        $staff = $this->makeUser('Staff', 'staff@example.test');
        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'test-password-123',
        ])->assertRedirect('/admin/dashboard');

        $this->assertNotSame($sessionId, $this->app['session']->getId());
        $this->post('/login', [
            'email' => $staff->email,
            'password' => 'test-password-123',
        ])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => $staff->email,
            'password' => 'test-password-123',
        ])->assertRedirect('/staff/dashboard');

        $member = $this->makeUser('Member', 'member@example.test');
        $this->post('/logout');
        $this->post('/login', [
            'email' => $member->email,
            'password' => 'test-password-123',
        ])->assertRedirect('/member/dashboard');
    }

    public function test_auth_forms_expose_accessible_errors_and_clear_submission_feedback(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('for="email"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('data-loading-label="Signing in..."', false)
            ->assertSee('Forgot your password?');

        $this->from('/login')->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ])->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('id="validation-summary" role="alert"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="email-error"', false)
            ->assertSee('id="email-error"', false)
            ->assertSee('missing@example.test');

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Back to log in');

        $this->get(route('password.reset', [
            'token' => str_repeat('a', 64),
            'email' => 'member@example.test',
        ]))
            ->assertOk()
            ->assertSee('Use at least 12 characters.')
            ->assertSee('Confirm new password')
            ->assertSee('data-loading-label="Resetting password..."', false);
    }

    public function test_invalid_and_disabled_credentials_share_a_generic_error(): void
    {
        $this->makeUser('Admin', 'disabled@example.test', 'Disabled');
        $payload = [
            'email' => 'disabled@example.test',
            'password' => 'incorrect-password',
        ];

        $this->from('/login')->post('/login', $payload)
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'The provided credentials are invalid.']);

        $this->from('/login')->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors(['email' => 'The provided credentials are invalid.']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        RateLimiter::clear('127.0.0.1');

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => 'missing@example.test',
                'password' => 'incorrect-password',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_members_can_only_update_their_own_profile_fields(): void
    {
        $memberUser = $this->makeUser('Member', 'member@example.test');
        $member = Member::create([
            'user_id' => $memberUser->user_id,
            'first_name' => 'Taylor',
            'last_name' => 'Member',
            'phone' => '555-0101',
            'member_status' => 'Active',
        ]);

        $this->actingAs($memberUser)
            ->get('/profile')
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee('555-0101');

        $this->put('/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Member',
            'email' => $memberUser->email,
            'phone' => '555-0199',
            'date_of_birth' => null,
            'role' => 'Admin',
            'account_status' => 'Disabled',
        ])->assertRedirect(route('profile.edit'));

        $memberUser->refresh();
        $member->refresh();
        $this->assertSame('Updated', $memberUser->first_name);
        $this->assertSame('Updated', $member->first_name);
        $this->assertSame('555-0199', $member->phone);
        $this->assertSame('Member', $memberUser->role);
        $this->assertSame('Active', $memberUser->account_status);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_change_requires_current_password_and_hashes_new_password(): void
    {
        $user = $this->makeUser('Staff', 'staff@example.test');
        $this->actingAs($user);

        $this->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'a-new-password-456',
            'password_confirmation' => 'a-new-password-456',
        ])->assertSessionHasErrors('current_password');

        $sessionId = $this->app['session']->getId();
        $this->put('/profile/password', [
            'current_password' => 'test-password-123',
            'password' => 'a-new-password-456',
            'password_confirmation' => 'a-new-password-456',
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertTrue(Hash::check('a-new-password-456', $user->password));
        $this->assertFalse(Hash::check('test-password-123', $user->password));
        $this->assertNotSame($sessionId, $this->app['session']->getId());
    }

    public function test_reset_links_are_generic_expiring_single_use_and_change_password(): void
    {
        $this->makeUser('Member', 'disabled@example.test', 'Disabled');
        Notification::fake();
        $user = $this->makeUser('Member', 'member@example.test');

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', 'If an active account exists for that email address, a password reset link has been sent.');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertIsString($token);
        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))->assertOk()->assertSee('Choose a new password');

        $this->put('/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-reset-password-789',
            'password_confirmation' => 'new-reset-password-789',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-reset-password-789', $user->fresh()->password));

        $this->from('/password/reset/'.$token)
            ->put('/password/reset', [
                'email' => $user->email,
                'token' => $token,
                'password' => 'another-reset-password-789',
                'password_confirmation' => 'another-reset-password-789',
            ])->assertSessionHasErrors('token');

        Notification::fake();
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertSessionHas('status', 'If an active account exists for that email address, a password reset link has been sent.');
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'disabled@example.test'])
            ->assertSessionHas('status', 'If an active account exists for that email address, a password reset link has been sent.');
        Notification::assertNothingSent();
    }

    public function test_reset_link_expires_after_the_configured_window(): void
    {
        Notification::fake();
        $user = $this->makeUser('Staff', 'staff@example.test');

        $this->post('/forgot-password', ['email' => $user->email]);
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->travel(61)->minutes();
        $this->from('/password/reset/'.$token)
            ->put('/password/reset', [
                'email' => $user->email,
                'token' => $token,
                'password' => 'expired-reset-password-789',
                'password_confirmation' => 'expired-reset-password-789',
            ])->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('test-password-123', $user->fresh()->password));
    }

    private function makeUser(string $role, string $email, string $status = 'Active'): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => $role,
            'email' => $email,
            'password' => 'test-password-123',
            'role' => $role,
            'account_status' => $status,
        ]);
    }
}
