<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\EmployeeInformation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeInformation>
 */
class EmployeeInformationFactory extends Factory
{
    protected $model = EmployeeInformation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_id' => 'EMP-'.fake()->unique()->numerify('####'),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'date_of_hire' => fake()->date(),
            'employment_status' => fake()->randomElement(EmploymentStatus::cases()),
            'salary' => fake()->randomFloat(2, 20000, 90000),
        ];
    }
}
