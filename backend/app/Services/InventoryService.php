<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function createMovement(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?string $reference = null,
        string $source = 'manual',
        ?User $actor = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $reference, $source, $actor): StockMovement {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $delta = $this->resolveDelta($type, $quantity);
            $updatedQuantity = $lockedProduct->quantity_on_hand + $delta;

            if ($updatedQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stock movement would reduce inventory below zero.'],
                ]);
            }

            $lockedProduct->update([
                'quantity_on_hand' => $updatedQuantity,
            ]);

            return StockMovement::query()->create([
                'product_id' => $lockedProduct->id,
                'type' => $type,
                'quantity' => $type === StockMovementType::Adjustment ? $quantity : abs($quantity),
                'reference' => $reference,
                'source' => $source,
                'created_by' => $actor?->id,
            ]);
        });
    }

    private function resolveDelta(StockMovementType $type, int $quantity): int
    {
        return match ($type) {
            StockMovementType::In => abs($quantity),
            StockMovementType::Out => -abs($quantity),
            StockMovementType::Adjustment => $quantity,
        };
    }
}
