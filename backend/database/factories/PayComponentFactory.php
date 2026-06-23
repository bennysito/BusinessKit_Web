<?php

namespace Database\Factories;

use App\Enums\PayComponentType;
use App\Models\PayComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayComponent>
 */
class PayComponentFactory extends Factory
{
    protected $model = PayComponent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' Component',
            'type' => fake()->randomElement(PayComponentType::cases()),
            'amount' => 500,
            'percentage' => null,
        ];
    }
}
