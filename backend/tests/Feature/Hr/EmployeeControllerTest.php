<?php

namespace Tests\Feature\Hr;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\EmployeeInformation;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_filter_search_and_manage_employee_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $sales = Department::factory()->create(['name' => 'Sales']);
        $hr = Department::factory()->create(['name' => 'Human Resources']);
        $managerPosition = Position::factory()->create(['name' => 'Manager']);
        $staffPosition = Position::factory()->create(['name' => 'Staff']);

        EmployeeInformation::factory()->create([
            'department_id' => $sales->id,
            'position_id' => $managerPosition->id,
            'employee_id' => 'EMP-9000',
            'first_name' => 'John',
            'last_name' => 'Sales',
            'employment_status' => EmploymentStatus::Active,
        ]);

        EmployeeInformation::factory()->create([
            'department_id' => $hr->id,
            'position_id' => $staffPosition->id,
            'employee_id' => 'EMP-9001',
            'first_name' => 'Anne',
            'last_name' => 'HR',
            'employment_status' => EmploymentStatus::Inactive,
        ]);

        $indexResponse = $this->getJson("/api/employees?department_id={$sales->id}&position_id={$managerPosition->id}&employment_status=active&search=EMP-9000&per_page=10");

        $indexResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_id', 'EMP-9000');

        $user = User::factory()->create([
            'name' => 'New Employee',
            'email' => 'new.employee@example.com',
        ]);

        $storeResponse = $this->postJson('/api/employees', [
            'user_id' => $user->id,
            'employee_id' => 'EMP-1000',
            'department_id' => $sales->id,
            'position_id' => $staffPosition->id,
            'first_name' => 'New',
            'last_name' => 'Employee',
            'email' => 'new.employee@example.com',
            'phone_number' => '123456789',
            'address' => '123 Example Street',
            'date_of_hire' => '2026-01-01',
            'employment_status' => EmploymentStatus::Active->value,
            'salary' => 45000,
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('employee_id', 'EMP-1000')
            ->assertJsonPath('department.name', 'Sales');

        $employeeId = $storeResponse->json('id');

        $this->putJson("/api/employees/{$employeeId}", [
            'employment_status' => EmploymentStatus::Probationary->value,
            'salary' => 47000,
        ])
            ->assertOk()
            ->assertJsonPath('employment_status', EmploymentStatus::Probationary->value)
            ->assertJsonPath('salary', '47000.00');

        $this->deleteJson("/api/employees/{$employeeId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('employee_information', [
            'id' => $employeeId,
        ]);
    }

    public function test_employee_only_sees_their_own_employee_record(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('employee');
        $otherUser = User::factory()->create();

        $ownEmployeeRecord = EmployeeInformation::factory()->create([
            'user_id' => $viewer->id,
            'email' => $viewer->email,
            'employee_id' => 'EMP-SELF',
        ]);

        $otherEmployeeRecord = EmployeeInformation::factory()->create([
            'user_id' => $otherUser->id,
            'employee_id' => 'EMP-OTHER',
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_id', 'EMP-SELF');

        $this->getJson("/api/employees/{$ownEmployeeRecord->id}")
            ->assertOk()
            ->assertJsonPath('employee_id', 'EMP-SELF');

        $this->getJson("/api/employees/{$otherEmployeeRecord->id}")
            ->assertForbidden();
    }

    public function test_employee_cannot_create_update_or_delete_employee_records(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');
        Sanctum::actingAs($employeeUser);

        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $targetUser = User::factory()->create();
        $record = EmployeeInformation::factory()->create();

        $this->postJson('/api/employees', [
            'user_id' => $targetUser->id,
            'employee_id' => 'EMP-2000',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'first_name' => 'Target',
            'last_name' => 'User',
            'email' => 'target.user@example.com',
            'employment_status' => EmploymentStatus::Active->value,
        ])->assertForbidden();

        $this->putJson("/api/employees/{$record->id}", [
            'first_name' => 'Updated',
        ])->assertForbidden();

        $this->deleteJson("/api/employees/{$record->id}")
            ->assertForbidden();
    }
}
