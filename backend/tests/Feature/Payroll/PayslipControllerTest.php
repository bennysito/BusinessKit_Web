<?php

namespace Tests\Feature\Payroll;

use App\Models\EmployeeInformation;
use App\Models\PayComponent;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayslipControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_hr_can_generate_payslip_and_mark_it_paid(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');
        Sanctum::actingAs($hr);

        $employee = EmployeeInformation::factory()->create([
            'salary' => 40000,
        ]);

        $earningComponent = PayComponent::factory()->create([
            'name' => 'Allowance',
            'type' => 'earning',
            'amount' => 2000,
            'percentage' => null,
        ]);

        $deductionComponent = PayComponent::factory()->create([
            'name' => 'Tax',
            'type' => 'deduction',
            'amount' => null,
            'percentage' => 10,
        ]);

        $employee->payComponents()->sync([$earningComponent->id, $deductionComponent->id]);

        $generateResponse = $this->postJson('/api/payslips/generate', [
            'employee_id' => $employee->id,
            'period' => '2026-06',
        ]);

        $generateResponse
            ->assertCreated()
            ->assertJsonPath('gross', '42000.00')
            ->assertJsonPath('deductions', '4000.00')
            ->assertJsonPath('net', '38000.00')
            ->assertJsonPath('status', 'draft')
            ->assertJsonCount(3, 'items');

        $payslipId = $generateResponse->json('id');

        $this->postJson("/api/payslips/{$payslipId}/mark-paid")
            ->assertOk()
            ->assertJsonPath('status', 'paid');
    }

    public function test_payroll_manage_permission_is_required_for_payslips(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        Sanctum::actingAs($employee);

        $this->getJson('/api/payslips')->assertForbidden();
    }
}
