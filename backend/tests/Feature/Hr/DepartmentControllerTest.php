<?php

namespace Tests\Feature\Hr;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_manage_departments(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        Department::factory()->create(['name' => 'Operations']);
        Department::factory()->create(['name' => 'Human Resources']);

        $indexResponse = $this->getJson('/api/departments?search=Human&per_page=1');

        $indexResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Human Resources');

        $storeResponse = $this->postJson('/api/departments', [
            'name' => 'Finance',
            'description' => 'Handles accounting.',
            'is_active' => true,
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Finance');

        $departmentId = $storeResponse->json('id');

        $updateResponse = $this->putJson("/api/departments/{$departmentId}", [
            'description' => 'Handles accounting and reporting.',
            'is_active' => false,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->deleteJson("/api/departments/{$departmentId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('departments', [
            'id' => $departmentId,
        ]);
    }

    public function test_employee_cannot_access_department_crud_routes(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        Sanctum::actingAs($employee);

        $this->getJson('/api/departments')->assertForbidden();
        $this->postJson('/api/departments', ['name' => 'Finance'])->assertForbidden();
    }
}
