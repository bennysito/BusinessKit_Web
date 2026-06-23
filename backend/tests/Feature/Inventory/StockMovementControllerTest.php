<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockMovementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_adjust_stock_and_quantity_is_updated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $product = Product::factory()->create([
            'quantity_on_hand' => 10,
        ]);

        $this->postJson('/api/inventory/stock-adjust', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
            'reference' => 'SALE-001',
        ])
            ->assertCreated()
            ->assertJsonPath('type', 'out');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity_on_hand' => 7,
        ]);

        $this->postJson('/api/inventory/stock-adjust', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => -2,
            'reference' => 'COUNT-ADJUST',
        ])
            ->assertCreated()
            ->assertJsonPath('quantity', -2);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity_on_hand' => 5,
        ]);
    }

    public function test_stock_adjustment_cannot_reduce_inventory_below_zero(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $product = Product::factory()->create([
            'quantity_on_hand' => 1,
        ]);

        $this->postJson('/api/inventory/stock-adjust', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }
}
