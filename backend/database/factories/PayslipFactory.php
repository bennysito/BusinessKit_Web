<?php

namespace Database\Factories;

use App\Enums\PayslipStatus;
use App\Models\EmployeeInformation;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => EmployeeInformation::factory(),
            'period' => now()->format('Y-m'),
            'gross' => '50000.00',
            'deductions' => '5000.00',
            'net' => '45000.00',
            'status' => PayslipStatus::Draft,
        ];
    }
}
