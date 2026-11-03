<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('membership_plans', function (Blueprint $table): void {
            $table->bigIncrements('plan_id');
            $table->string('plan_name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 10, 2);
            $table->string('plan_status', 20);
            $table->timestamps();
        });
    }

    public function test_public_landing_page_displays_active_database_plans_only(): void
    {
        DB::table('membership_plans')->insert([
            'plan_name' => 'Monthly Plan',
            'description' => 'Flexible monthly access.',
            'duration_days' => 30,
            'price' => '49.00',
            'plan_status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('membership_plans')->insert([
            'plan_name' => 'Inactive Plan',
            'description' => 'Not available.',
            'duration_days' => 60,
            'price' => '99.00',
            'plan_status' => 'Inactive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Your fitness journey')
            ->assertSee('Monthly Plan')
            ->assertSee('49.00')
            ->assertDontSee('Inactive Plan')
            ->assertSee(route('login'));
    }
}
