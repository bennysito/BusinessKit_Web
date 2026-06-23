<?php

namespace Tests\Feature\Leave;

use App\Models\EmployeeInformation;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_hr_can_manage_leave_types_and_employee_cannot(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');
        Sanctum::actingAs($hr);

        $storeResponse = $this->postJson('/api/leave-types', [
            'name' => 'Vacation',
            'default_days' => 12,
            'is_paid' => true,
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Vacation');

        $leaveTypeId = $storeResponse->json('id');

        $this->putJson("/api/leave-types/{$leaveTypeId}", [
            'default_days' => 15,
        ])
            ->assertOk()
            ->assertJsonPath('default_days', 15);

        $this->deleteJson("/api/leave-types/{$leaveTypeId}")
            ->assertNoContent();

        $employee = User::factory()->create();
        $employee->assignRole('employee');
        Sanctum::actingAs($employee);

        $this->getJson('/api/leave-types')->assertForbidden();
    }

    public function test_leave_approval_deducts_balance_and_cancel_restores_it(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');
        $employee = EmployeeInformation::factory()->create([
            'user_id' => $employeeUser->id,
            'email' => $employeeUser->email,
        ]);

        $leaveType = LeaveType::factory()->create([
            'default_days' => 10,
        ]);

        LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'entitled' => 10,
            'used' => 0,
        ]);

        Sanctum::actingAs($employeeUser);

        $requestResponse = $this->postJson('/api/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'reason' => 'Family trip',
        ]);

        $requestResponse
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('days', '3.00');

        $leaveRequestId = $requestResponse->json('id');

        $approver = User::factory()->create();
        $approver->assignRole('hr');
        Sanctum::actingAs($approver);

        $this->postJson("/api/leave-requests/{$leaveRequestId}/approve")
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'used' => 3,
        ]);

        Sanctum::actingAs($employeeUser);

        $this->postJson("/api/leave-requests/{$leaveRequestId}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'used' => 0,
        ]);
    }

    public function test_employee_only_sees_their_own_leave_requests(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('employee');
        $viewerEmployee = EmployeeInformation::factory()->create([
            'user_id' => $viewer->id,
            'email' => $viewer->email,
        ]);

        $otherUser = User::factory()->create();
        $otherEmployee = EmployeeInformation::factory()->create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
        ]);

        $viewerRequest = LeaveRequest::factory()->create([
            'employee_id' => $viewerEmployee->id,
        ]);
        $otherRequest = LeaveRequest::factory()->create([
            'employee_id' => $otherEmployee->id,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $viewerRequest->id);

        $this->getJson("/api/leave-requests/{$otherRequest->id}")
            ->assertForbidden();
    }
}
