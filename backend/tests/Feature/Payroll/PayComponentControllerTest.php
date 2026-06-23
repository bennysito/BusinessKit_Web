<?php

namespace Tests\Feature\Payroll;

use App\Models\EmployeeInformation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayComponentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_hr_can_manage_pay_components_and_assign_employees(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');
        Sanctum::actingAs($hr);

        $employee = EmployeeInformation::factory()->create();

        $storeResponse = $this->postJson('/api/pay-components', [
            'name' => 'Transport Allowance',
            'type' => 'earning',
            'amount' => 1500,
            'employee_ids' => [$employee->id],
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Transport Allowance')
            ->assertJsonPath('employee_ids.0', $employee->id);

        $componentId = $storeResponse->json('id');

        $this->putJson("/api/pay-components/{$componentId}", [
            'percentage' => 10,
            'amount' => null,
            'employee_ids' => [],
        ])
            ->assertOk()
            ->assertJsonPath('percentage', '10.00');

        $this->deleteJson("/api/pay-components/{$componentId}")
            ->assertNoContent();
    }

    public function test_employee_cannot_manage_pay_components(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        Sanctum::actingAs($employee);

        $this->getJson('/api/pay-components')->assertForbidden();
    }
}
