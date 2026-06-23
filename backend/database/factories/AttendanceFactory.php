<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\EmployeeInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now()->subDays(fake()->numberBetween(0, 30));
        $clockIn = (clone $date)->setTime(8, 30);
        $clockOut = (clone $date)->setTime(17, 0);

        return [
            'employee_id' => EmployeeInformation::factory(),
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => AttendanceStatus::Present,
            'hours_worked' => '8.50',
            'notes' => fake()->sentence(),
        ];
    }
}
