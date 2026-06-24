<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_reference' => 'POS-'.fake()->unique()->numerify('####'),
            'sold_at' => now(),
            'cashier' => fake()->name(),
            'subtotal' => '100.00',
            'tax' => '12.00',
            'total' => '112.00',
            'payment_method' => 'cash',
            'status' => SaleStatus::Completed,
            'synced_at' => now(),
        ];
    }
}
