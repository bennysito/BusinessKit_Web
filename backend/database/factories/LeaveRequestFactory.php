<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\EmployeeInformation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->startOfMonth()->addDays(fake()->numberBetween(1, 10));
        $endDate = (clone $startDate)->addDays(fake()->numberBetween(0, 2));
        $days = $startDate->diffInDays($endDate) + 1;

        return [
            'employee_id' => EmployeeInformation::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days' => number_format($days, 2, '.', ''),
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::Pending,
            'approver_id' => null,
            'decided_at' => null,
        ];
    }
}
