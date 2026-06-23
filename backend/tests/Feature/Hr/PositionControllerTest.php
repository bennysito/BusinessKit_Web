<?php

namespace Tests\Feature\Hr;

use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_hr_user_can_manage_positions(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');
        Sanctum::actingAs($hr);

        Position::factory()->create(['name' => 'Clerk']);
        Position::factory()->create(['name' => 'Supervisor']);

        $indexResponse = $this->getJson('/api/positions?search=Supervisor&per_page=1');

        $indexResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Supervisor');

        $storeResponse = $this->postJson('/api/positions', [
            'name' => 'Analyst',
            'description' => 'Analyzes operational metrics.',
            'is_active' => true,
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Analyst');

        $positionId = $storeResponse->json('id');

        $this->putJson("/api/positions/{$positionId}", [
            'description' => 'Analyzes operational and reporting metrics.',
        ])->assertOk();

        $this->deleteJson("/api/positions/{$positionId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('positions', [
            'id' => $positionId,
        ]);
    }

    public function test_employee_cannot_access_position_crud_routes(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        Sanctum::actingAs($employee);

        $this->getJson('/api/positions')->assertForbidden();
        $this->postJson('/api/positions', ['name' => 'Analyst'])->assertForbidden();
    }
}
