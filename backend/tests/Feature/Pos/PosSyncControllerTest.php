<?php

namespace Tests\Feature\Pos;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_pos_sync_is_idempotent_and_decrements_stock(): void
    {
        $integrationUser = User::factory()->create();
        $integrationUser->givePermissionTo('pos.sync');
        Sanctum::actingAs($integrationUser, ['pos.sync']);

        Product::factory()->create([
            'sku' => 'SKU-001',
            'quantity_on_hand' => 10,
        ]);

        $payload = [
            'sales' => [
                [
                    'external_reference' => 'POS-2026-0001',
                    'sold_at' => '2026-06-15T10:32:00Z',
                    'cashier' => 'Jane D.',
                    'payment_method' => 'cash',
                    'subtotal' => 100,
                    'tax' => 12,
                    'total' => 112,
                    'items' => [
                        [
                            'sku' => 'SKU-001',
                            'quantity' => 2,
                            'unit_price' => 50,
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/pos/sync', $payload)
            ->assertCreated()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('skipped', 0)
            ->assertJsonPath('sales.0.external_reference', 'POS-2026-0001')
            ->assertJsonPath('sales.0.items.0.sku', 'SKU-001');

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'quantity_on_hand' => 8,
        ]);

        $this->postJson('/api/pos/sync', $payload)
            ->assertCreated()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('skipped', 1);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseHas('stock_movements', [
            'source' => 'pos',
            'reference' => 'POS-2026-0001',
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'quantity_on_hand' => 8,
        ]);
    }

    public function test_reports_view_users_can_list_sales_and_view_summary(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $sale = Sale::factory()->create([
            'external_reference' => 'POS-REPORT-1',
            'payment_method' => 'card',
            'sold_at' => '2026-06-20 09:00:00',
            'total' => '112.00',
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'sku' => 'SKU-REPORT',
            'quantity' => 3,
            'line_total' => '150.00',
        ]);

        $this->getJson('/api/pos/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_reference', 'POS-REPORT-1');

        $this->getJson('/api/pos/sales/summary?group_by=product')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'SKU-REPORT')
            ->assertJsonPath('data.0.quantity', 3);
    }

    public function test_pos_sync_rejects_invalid_sale_status(): void
    {
        $integrationUser = User::factory()->create();
        $integrationUser->givePermissionTo('pos.sync');
        Sanctum::actingAs($integrationUser, ['pos.sync']);

        Product::factory()->create([
            'sku' => 'SKU-INVALID-STATUS',
            'quantity_on_hand' => 10,
        ]);

        $payload = [
            'sales' => [
                [
                    'external_reference' => 'POS-2026-INVALID',
                    'sold_at' => '2026-06-15T10:32:00Z',
                    'cashier' => 'Jane D.',
                    'payment_method' => 'cash',
                    'status' => 'invalid',
                    'subtotal' => 100,
                    'tax' => 12,
                    'total' => 112,
                    'items' => [
                        [
                            'sku' => 'SKU-INVALID-STATUS',
                            'quantity' => 2,
                            'unit_price' => 50,
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/pos/sync', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sales.0.status']);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-INVALID-STATUS',
            'quantity_on_hand' => 10,
        ]);
    }
}
