<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(StockMovementType::cases()),
            'quantity' => fake()->numberBetween(1, 10),
            'reference' => fake()->bothify('REF-####'),
            'source' => 'manual',
            'created_by' => User::factory(),
        ];
    }
}
