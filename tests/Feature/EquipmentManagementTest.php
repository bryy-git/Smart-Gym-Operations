<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentUsage;
use App\Models\MaintenanceRecord;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EquipmentManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'equipment_usage',
            'maintenance_records',
            'equipment',
            'notifications',
            'attendance_corrections',
            'attendance',
            'system_settings',
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
            $table->foreign('equipment_id')->references('equipment_id')->on('equipment');
            $table->foreign('reported_by')->references('user_id')->on('users');
            $table->foreign('updated_by')->references('user_id')->on('users');
        });

        Schema::create('equipment_usage', function (Blueprint $table): void {
            $table->bigIncrements('usage_id');
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedInteger('usage_count');
            $table->date('usage_date');
            $table->text('notes')->nullable();
            $table->foreign('equipment_id')->references('equipment_id')->on('equipment');
            $table->foreign('recorded_by')->references('user_id')->on('users');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_and_staff_can_manage_equipment_but_members_cannot(): void
    {
        $staff = $this->makeUser('Staff', 'staff@example.test');

        $this->actingAs($staff)
            ->get(route('staff.equipment.index'))
            ->assertOk()
            ->assertSee('Equipment');
        $this->get(route('staff.equipment.create'))
            ->assertOk()
            ->assertSee('Add Equipment');

        $this->post(route('staff.equipment.store'), [
            'equipment_name' => 'Treadmill A',
            'description' => 'Main floor',
            'equipment_status' => 'Available',
            'usage_threshold' => 100,
        ])->assertRedirect();

        $equipment = Equipment::query()->firstOrFail();
        $this->assertSame('Available', $equipment->equipment_status);
        $this->get(route('staff.equipment.show', $equipment))
            ->assertOk()
            ->assertSee('Treadmill A')
            ->assertSee('Report equipment issue');
        $this->get(route('staff.equipment.edit', $equipment))
            ->assertOk()
            ->assertSee('Edit Equipment');

        $this->put(route('staff.equipment.update', $equipment), [
            'equipment_name' => 'Treadmill A2',
            'description' => 'Updated floor',
            'equipment_status' => 'Unavailable',
            'usage_threshold' => '',
        ])->assertRedirect(route('staff.equipment.show', $equipment));

        $this->assertSame('Unavailable', $equipment->fresh()->equipment_status);
        $member = $this->makeUser('Member', 'member@example.test');
        $this->actingAs($member)
            ->get(route('staff.equipment.index'))
            ->assertForbidden();
        $this->delete('/staff/equipment/'.$equipment->equipment_id)->assertMethodNotAllowed();
    }

    public function test_maintenance_reporting_updates_status_and_preserves_history(): void
    {
        Carbon::setTestNow('2026-04-15 10:00:00');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $equipment = $this->makeEquipment();

        $this->actingAs($staff)
            ->post(route('staff.equipment.maintenance.store', $equipment), [
                'issue_description' => 'Motor is making a loud noise',
            ])->assertRedirect(route('staff.equipment.show', $equipment));

        $firstRecord = MaintenanceRecord::query()->firstOrFail();
        $this->assertSame('Reported', $firstRecord->maintenance_status);
        $this->assertSame($staff->user_id, $firstRecord->reported_by);
        $this->assertSame('Under Maintenance', $equipment->fresh()->equipment_status);

        $this->put(route('staff.equipment.update', $equipment), [
            'equipment_name' => $equipment->equipment_name,
            'description' => $equipment->description,
            'equipment_status' => 'Available',
            'usage_threshold' => $equipment->usage_threshold,
        ])->assertSessionHasErrors('equipment_status');

        $this->get(route('staff.equipment.maintenance-history', $equipment))
            ->assertOk()
            ->assertSee('Motor is making a loud noise');

        $this->patch(route('staff.equipment.maintenance.update', [$equipment, $firstRecord]), [
            'maintenance_status' => 'In Progress',
            'action_details' => 'Replacement part ordered',
        ])->assertRedirect(route('staff.equipment.maintenance-history', $equipment));
        $this->assertSame('In Progress', $firstRecord->fresh()->maintenance_status);
        $this->assertNull($firstRecord->fresh()->resolved_at);

        $this->post(route('staff.equipment.maintenance.store', $equipment), [
            'issue_description' => 'Loose safety cover',
        ])->assertRedirect();
        $secondRecord = MaintenanceRecord::query()
            ->where('maintenance_id', '!=', $firstRecord->maintenance_id)
            ->firstOrFail();

        $this->patch(route('staff.equipment.maintenance.update', [$equipment, $firstRecord]), [
            'maintenance_status' => 'Resolved',
            'action_details' => 'Motor repaired',
        ])->assertRedirect();
        $this->assertNotNull($firstRecord->fresh()->resolved_at);
        $this->assertSame('Under Maintenance', $equipment->fresh()->equipment_status);

        $this->patch(route('staff.equipment.maintenance.update', [$equipment, $secondRecord]), [
            'maintenance_status' => 'Resolved',
            'action_details' => 'Cover tightened',
        ])->assertRedirect();
        $this->assertSame('Available', $equipment->fresh()->equipment_status);
        $this->assertDatabaseCount('maintenance_records', 2);
        $this->delete('/staff/equipment/'.$equipment->equipment_id.'/maintenance/'.$firstRecord->maintenance_id)
            ->assertMethodNotAllowed();
    }

    public function test_usage_records_drive_threshold_alerts_since_latest_resolved_maintenance(): void
    {
        Carbon::setTestNow('2026-04-15 12:00:00');
        $admin = $this->makeUser('Admin', 'admin@example.test');
        $staff = $this->makeUser('Staff', 'staff@example.test');
        $equipment = $this->makeEquipment('Leg Press', 'Available', 10);
        $resolvedMaintenance = $this->makeMaintenance(
            $equipment,
            $admin,
            'Resolved',
            Carbon::parse('2026-04-14 08:00:00'),
        );
        $this->makeUsage($equipment, $staff, 50, '2026-04-13');
        $this->makeUsage($equipment, $staff, 9, '2026-04-14');

        $this->actingAs($staff)
            ->get(route('staff.equipment.index'))
            ->assertOk()
            ->assertSee('4')
            ->assertSee('No alert');

        $this->post(route('staff.equipment.usage.store', $equipment), [
            'usage_count' => 6,
            'usage_date' => '2026-04-15',
            'notes' => 'Daily usage count',
        ])->assertRedirect(route('staff.equipment.usage-history', $equipment));
        $this->get(route('staff.equipment.index'))
            ->assertOk()
            ->assertSee('Attention required');
        $this->assertDatabaseHas('equipment_usage', [
            'equipment_id' => $equipment->equipment_id,
            'recorded_by' => $staff->user_id,
            'usage_count' => 6,
            'notes' => 'Daily usage count',
        ]);

        $this->get(route('staff.equipment.usage-history', $equipment))
            ->assertOk()
            ->assertSee('Daily usage count');
        $this->actingAs($admin)
            ->get(route('admin.equipment.show', $equipment))
            ->assertOk()
            ->assertSee('Usage since last resolved maintenance');

        $this->actingAs($staff)
            ->post(route('staff.equipment.usage.store', $equipment), [
                'usage_count' => 1,
                'usage_date' => '2999-01-01',
            ])->assertSessionHasErrors('usage_date');

        $this->assertNotNull($resolvedMaintenance->fresh()->resolved_at);
        $this->assertDatabaseCount('equipment_usage', 3);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::query()->create([
            'first_name' => ucfirst($role),
            'last_name' => 'Test',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'account_status' => 'Active',
        ]);
    }

    private function makeEquipment(
        string $name = 'Bench Press',
        string $status = 'Available',
        ?int $threshold = null,
    ): Equipment {
        return Equipment::query()->create([
            'equipment_name' => $name,
            'description' => null,
            'equipment_status' => $status,
            'usage_threshold' => $threshold,
        ]);
    }

    private function makeMaintenance(
        Equipment $equipment,
        User $reporter,
        string $status,
        Carbon $reportedAt,
    ): MaintenanceRecord {
        return $equipment->maintenanceRecords()->create([
            'reported_by' => $reporter->user_id,
            'issue_description' => 'Historical maintenance',
            'maintenance_status' => $status,
            'action_details' => null,
            'reported_at' => $reportedAt,
            'resolved_at' => $status === 'Resolved' ? $reportedAt : null,
        ]);
    }

    private function makeUsage(Equipment $equipment, User $recorder, int $count, string $date): EquipmentUsage
    {
        return $equipment->usageRecords()->create([
            'recorded_by' => $recorder->user_id,
            'usage_count' => $count,
            'usage_date' => $date,
            'notes' => null,
        ]);
    }
}
