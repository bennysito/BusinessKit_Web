<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_manage_categories_products_and_low_stock_listing(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $categoryResponse = $this->postJson('/api/inventory/categories', [
            'name' => 'Beverages',
            'description' => 'Drinks and refreshments',
        ]);

        $categoryResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Beverages');

        $categoryId = $categoryResponse->json('id');

        $productResponse = $this->postJson('/api/inventory/products', [
            'sku' => 'SKU-0001',
            'name' => 'Orange Juice',
            'category_id' => $categoryId,
            'price' => 120,
            'cost' => 80,
            'quantity_on_hand' => 2,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $productResponse
            ->assertCreated()
            ->assertJsonPath('sku', 'SKU-0001');

        $productId = $productResponse->json('id');

        $this->putJson("/api/inventory/products/{$productId}", [
            'price' => 130,
            'reorder_level' => 4,
        ])
            ->assertOk()
            ->assertJsonPath('price', '130.00');

        Product::factory()->create([
            'quantity_on_hand' => 10,
            'reorder_level' => 5,
        ]);

        $this->getJson('/api/inventory/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'SKU-0001');

        $this->deleteJson("/api/inventory/products/{$productId}")
            ->assertNoContent();
        $this->deleteJson("/api/inventory/categories/{$categoryId}")
            ->assertNoContent();
    }

    public function test_employee_cannot_access_inventory_routes(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        Sanctum::actingAs($employee);

        $this->getJson('/api/inventory/products')->assertForbidden();
    }
}
