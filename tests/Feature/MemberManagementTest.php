<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase as BaseTestCase;

class MemberManagementTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_admin_and_staff_routes_are_role_and_account_status_protected(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $member = $this->makeUser('Member', 'member@example.test');

        $this->actingAs($admin)->get('/admin/members')->assertOk()->assertSee('Admin / Members');
        $this->actingAs($admin)->get('/staff/members')->assertForbidden();
        $this->actingAs($staff)->get('/staff/members')->assertOk()->assertSee('Staff / Members');
        $this->actingAs($staff)->get('/admin/members')->assertForbidden();
        $this->actingAs($member)->get('/admin/members')->assertForbidden();
        $this->actingAs($member)->get('/staff/members')->assertForbidden();

        $disabledAdmin = $this->makeUser('Admin', 'disabled-admin@example.test', 'Disabled');
        $this->actingAs($disabledAdmin)->get('/admin/members')->assertForbidden();
    }

    public function test_guests_are_redirected_and_member_routes_have_no_delete_endpoint(): void
    {
        $this->get('/admin/members')->assertRedirect('/login');
        $this->get('/staff/members')->assertRedirect('/login');

        foreach (app('router')->getRoutes() as $route) {
            if (str_contains($route->uri(), 'members')) {
                $this->assertNotContains('DELETE', $route->methods());
            }
        }
    }

    public function test_member_list_searches_case_insensitively_filters_and_preserves_pagination_query(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->actingAs($admin);

        $this->makeMember($admin, 'mIxEd', 'Needle', 'mixedneedle@example.test');
        $this->makeMember($admin, 'Other', 'Person', 'other@example.test', 'Inactive', 'Disabled');

        $this->get('/admin/members?search=IXEDNEE&status=Active')
            ->assertOk()
            ->assertSee('Members')
            ->assertSee('Manage member profiles and their accounts.')
            ->assertSee('mIxEd Needle')
            ->assertSee('mixedneedle@example.test')
            ->assertDontSee('Other Person');

        $this->get('/admin/members?search=needle%40example.test')
            ->assertOk()
            ->assertSee('mIxEd Needle')
            ->assertDontSee('Other Person');

        for ($index = 0; $index < 10; $index++) {
            $this->makeMember(
                $admin,
                'Needle',
                sprintf('Person %02d', $index),
                sprintf('needle%02d@example.test', $index),
            );
        }

        $this->get('/admin/members?search=needle&status=Active')
            ->assertOk()
            ->assertSee('search=needle&amp;status=Active&amp;page=2', false);

        $this->get('/admin/members?search=needle&status=Active&page=2')
            ->assertOk()
            ->assertSee('Person 09')
            ->assertDontSee('Person 08');
    }

    public function test_empty_and_filtered_member_states_are_displayed(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');

        $this->actingAs($admin)
            ->get('/admin/members')
            ->assertOk()
            ->assertSee('No members have been added yet.');

        $this->makeMember($admin, 'Only', 'Active', 'active@example.test');
        $this->makeMember($admin, 'Mismatch', 'Status', 'mismatch@example.test', 'Active', 'Disabled');

        $this->get('/admin/members')
            ->assertOk()
            ->assertSee('Member and user account statuses do not match.');

        $this->get('/admin/members?search=not-found&status=Inactive')
            ->assertOk()
            ->assertSee('No search results.')
            ->assertSee('Clear filters');
    }

    public function test_member_creation_validates_hashes_password_and_creates_both_records(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->actingAs($admin);
        $this->get('/admin/members/create')
            ->assertOk()
            ->assertSee('Initial Password')
            ->assertDontSee('name="role"');

        $payload = [
            'first_name' => 'Taylor',
            'last_name' => 'Member',
            'email' => 'taylor@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
            'phone' => '+1 555 0100',
            'date_of_birth' => '2000-02-29',
        ];

        $response = $this->post('/admin/members', $payload);
        $user = User::where('email', 'taylor@example.test')->firstOrFail();
        $member = Member::where('user_id', $user->user_id)->firstOrFail();

        $response->assertRedirect(route('admin.members.show', $member));
        $this->assertSame('Member', $user->role);
        $this->assertSame('Active', $user->account_status);
        $this->assertSame('Active', $member->member_status);
        $this->assertSame('+1 555 0100', $member->phone);
        $this->assertSame('2000-02-29', $member->date_of_birth->toDateString());
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertNotSame($payload['password'], $user->password);
        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');
        $this->get(route('admin.members.show', $member))
            ->assertOk()
            ->assertSee('Taylor Member')
            ->assertSee('+1 555 0100')
            ->assertDontSee($user->password);
    }

    public function test_create_validation_preserves_phone_but_not_passwords(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->actingAs($admin)
            ->from('/admin/members/create')
            ->post('/admin/members', [
                'first_name' => 'Taylor',
                'last_name' => 'Member',
                'email' => 'invalid',
                'password' => 'short',
                'password_confirmation' => 'different',
                'phone' => '555-0101',
                'date_of_birth' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect('/admin/members/create')
            ->assertSessionHas('_old_input.phone', '555-0101')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation')
            ->assertSessionHasErrors(['email', 'password', 'date_of_birth']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_phone_number_is_required_when_creating_a_member(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');

        $this->actingAs($admin)
            ->from('/admin/members/create')
            ->post('/admin/members', [
                'first_name' => 'Taylor',
                'last_name' => 'Member',
                'email' => 'taylor@example.test',
                'password' => 'a-very-long-password',
                'password_confirmation' => 'a-very-long-password',
                'date_of_birth' => null,
            ])
            ->assertRedirect('/admin/members/create')
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_member_creation_rolls_back_the_user_if_member_creation_fails(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $this->actingAs($admin)->withoutExceptionHandling();

        Member::creating(function (): void {
            throw new RuntimeException('Simulated member creation failure.');
        });

        try {
            $this->post('/admin/members', [
                'first_name' => 'Taylor',
                'last_name' => 'Member',
                'email' => 'taylor@example.test',
                'password' => 'a-very-long-password',
                'password_confirmation' => 'a-very-long-password',
                'phone' => '555-0101',
                'date_of_birth' => null,
            ]);
            $this->fail('The simulated member creation failure should have been thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated member creation failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_member_edit_updates_both_profiles_and_rejects_another_users_email(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $member = $this->makeMember($admin, 'Casey', 'Member', 'casey@example.test');
        $otherUser = $this->makeUser('Member', 'taken@example.test');
        $this->actingAs($admin);
        $this->get('/admin/members/'.$member->member_id.'/edit')
            ->assertOk()
            ->assertDontSee('name="password"');

        $this->put('/admin/members/'.$member->member_id, [
            'first_name' => 'Updated',
            'last_name' => 'Member',
            'email' => 'casey@example.test',
            'phone' => '555-0200',
            'date_of_birth' => null,
        ])->assertRedirect(route('admin.members.show', $member));

        $member->refresh();
        $member->user->refresh();
        $this->assertSame('Updated', $member->first_name);
        $this->assertSame('Updated', $member->user->first_name);
        $this->assertSame('555-0200', $member->phone);

        $this->from('/admin/members/'.$member->member_id.'/edit')
            ->put('/admin/members/'.$member->member_id, [
                'first_name' => 'Updated',
                'last_name' => 'Member',
                'email' => $otherUser->email,
                'phone' => '555-0200',
                'date_of_birth' => null,
            ])
            ->assertRedirect('/admin/members/'.$member->member_id.'/edit')
            ->assertSessionHasErrors('email');
    }

    public function test_member_profile_update_rolls_back_the_user_if_member_update_fails(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $member = $this->makeMember($admin, 'Casey', 'Member', 'casey@example.test');
        $this->actingAs($admin)->withoutExceptionHandling();

        Member::saving(function (Member $savingMember): void {
            if ($savingMember->first_name === 'Rollback') {
                throw new RuntimeException('Simulated member update failure.');
            }
        });

        try {
            $this->put('/admin/members/'.$member->member_id, [
                'first_name' => 'Rollback',
                'last_name' => 'Member',
                'email' => 'updated@example.test',
                'phone' => '555-0200',
                'date_of_birth' => null,
            ]);
            $this->fail('The simulated member update failure should have been thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated member update failure.', $exception->getMessage());
        }

        $this->assertSame('Casey', $member->fresh()->first_name);
        $this->assertSame('Test', $member->user->fresh()->first_name);
        $this->assertSame('casey@example.test', $member->user->fresh()->email);
        $this->assertSame('555-0000', $member->fresh()->phone);
    }

    public function test_staff_can_manage_members_using_staff_routes(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $this->actingAs($staff);

        $this->post('/staff/members', [
            'first_name' => 'Taylor',
            'last_name' => 'Member',
            'email' => 'taylor@example.test',
            'password' => 'a-very-long-password',
            'password_confirmation' => 'a-very-long-password',
            'phone' => '555-0101',
            'date_of_birth' => null,
        ]);

        $member = Member::query()->where('first_name', 'Taylor')->firstOrFail();

        $this->get(route('staff.members.show', $member))
            ->assertOk()
            ->assertSee('Taylor Member');

        $this->put(route('staff.members.update', $member), [
            'first_name' => 'Updated',
            'last_name' => 'Member',
            'email' => 'taylor@example.test',
            'phone' => '555-0200',
            'date_of_birth' => null,
        ])->assertRedirect(route('staff.members.show', $member));

        $this->patch(route('staff.members.status.update', [$member, 'Inactive']), [
            'confirm_status_change' => '1',
        ])->assertRedirect(route('staff.members.show', $member));

        $this->assertSame('Inactive', $member->fresh()->member_status);
        $this->assertSame('Disabled', $member->user->fresh()->account_status);

        $this->patch(route('staff.members.status.update', [$member, 'Active']), [
            'confirm_status_change' => '1',
        ])->assertRedirect(route('staff.members.show', $member));

        $this->assertSame('Active', $member->fresh()->member_status);
        $this->assertSame('Active', $member->user->fresh()->account_status);
    }

    public function test_status_changes_require_confirmation_and_synchronize_both_records(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $member = $this->makeMember($admin, 'Jordan', 'Member', 'jordan@example.test', 'Active', 'Disabled');
        $this->actingAs($admin);

        $this->get('/admin/members/'.$member->member_id.'/status/Inactive')
            ->assertOk()
            ->assertSee('Current Member Status')
            ->assertSee('Active')
            ->assertSee('Current User Account Status')
            ->assertSee('Disabled')
            ->assertSee('Target Member Status')
            ->assertSee('Target User Account Status');

        $this->patch('/admin/members/'.$member->member_id.'/status/Inactive')
            ->assertSessionHasErrors('confirm_status_change');
        $this->assertSame('Active', $member->fresh()->member_status);
        $this->assertSame('Disabled', $member->user->fresh()->account_status);

        $this->patch('/admin/members/'.$member->member_id.'/status/Inactive', [
            'confirm_status_change' => '1',
        ])->assertRedirect(route('admin.members.show', $member));
        $this->assertSame('Inactive', $member->fresh()->member_status);
        $this->assertSame('Disabled', $member->user->fresh()->account_status);

        $this->patch('/admin/members/'.$member->member_id.'/status/Active', [
            'confirm_status_change' => '1',
        ])->assertRedirect(route('admin.members.show', $member));
        $this->assertSame('Active', $member->fresh()->member_status);
        $this->assertSame('Active', $member->user->fresh()->account_status);
    }

    public function test_status_transaction_rolls_back_if_linked_user_update_fails(): void
    {
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $member = $this->makeMember($admin, 'Riley', 'Member', 'riley@example.test');
        $this->actingAs($admin)->withoutExceptionHandling();

        User::saving(function (User $user): void {
            if ($user->account_status === 'Disabled') {
                throw new RuntimeException('Simulated account update failure.');
            }
        });

        try {
            $this->patch('/admin/members/'.$member->member_id.'/status/Inactive', [
                'confirm_status_change' => '1',
            ]);
            $this->fail('The simulated update failure should have been thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated account update failure.', $exception->getMessage());
        }

        $this->assertSame('Active', $member->fresh()->member_status);
        $this->assertSame('Active', $member->user->fresh()->account_status);
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

    private function makeMember(
        User $creator,
        string $firstName,
        string $lastName,
        string $email,
        string $memberStatus = 'Active',
        string $accountStatus = 'Active',
    ): Member {
        $user = $this->makeUser('Member', $email, $accountStatus);

        return Member::create([
            'user_id' => $user->user_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '555-0000',
            'member_status' => $memberStatus,
            'created_by' => $creator->user_id,
        ]);
    }
}
