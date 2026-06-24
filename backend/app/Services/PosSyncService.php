<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosSyncService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $sales
     * @return array{created:int, skipped:int, synced_sales:array<int, Sale>}
     */
    public function sync(array $sales, User $actor): array
    {
        return DB::transaction(function () use ($sales, $actor): array {
            $created = 0;
            $skipped = 0;
            $syncedSales = [];

            foreach ($sales as $salePayload) {
                $existingSale = Sale::query()
                    ->where('external_reference', $salePayload['external_reference'])
                    ->first();

                if ($existingSale) {
                    $skipped++;
                    $syncedSales[] = $existingSale->load('items.product');

                    continue;
                }

                $sale = Sale::query()->create([
                    'external_reference' => $salePayload['external_reference'],
                    'sold_at' => $salePayload['sold_at'],
                    'cashier' => $salePayload['cashier'] ?? null,
                    'subtotal' => $salePayload['subtotal'],
                    'tax' => $salePayload['tax'],
                    'total' => $salePayload['total'],
                    'payment_method' => $salePayload['payment_method'],
                    'status' => $salePayload['status'] ?? SaleStatus::Completed,
                    'synced_at' => now(),
                ]);

                foreach ($salePayload['items'] as $itemPayload) {
                    $product = Product::query()
                        ->where('sku', $itemPayload['sku'])
                        ->first();

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'sales' => ["Unable to match SKU [{$itemPayload['sku']}] to a product."],
                        ]);
                    }

                    $lineTotal = round($itemPayload['quantity'] * $itemPayload['unit_price'], 2);

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'sku' => $itemPayload['sku'],
                        'quantity' => $itemPayload['quantity'],
                        'unit_price' => $itemPayload['unit_price'],
                        'line_total' => number_format($lineTotal, 2, '.', ''),
                    ]);

                    $this->inventoryService->createMovement(
                        $product,
                        StockMovementType::Out,
                        (int) $itemPayload['quantity'],
                        $sale->external_reference,
                        'pos',
                        $actor,
                    );
                }

                $created++;
                $syncedSales[] = $sale->refresh()->load('items.product');
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
                'synced_sales' => $syncedSales,
            ];
        });
    }
}
