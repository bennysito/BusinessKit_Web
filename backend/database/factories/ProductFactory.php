<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(2, true),
            'category_id' => ProductCategory::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'cost' => fake()->randomFloat(2, 5, 300),
            'quantity_on_hand' => fake()->numberBetween(0, 20),
            'reorder_level' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
