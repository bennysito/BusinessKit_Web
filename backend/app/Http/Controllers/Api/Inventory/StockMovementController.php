<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StockMovement::class);

        $movements = StockMovement::query()
            ->with('product')
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->string('source')->toString()))
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return StockMovementResource::collection($movements);
    }

    public function store(
        StockAdjustmentRequest $request,
        InventoryService $inventoryService,
    ): StockMovementResource {
        $this->authorize('create', StockMovement::class);

        $product = Product::query()->findOrFail($request->integer('product_id'));
        $movement = $inventoryService->createMovement(
            $product,
            StockMovementType::from($request->string('type')->toString()),
            $request->integer('quantity'),
            $request->string('reference')->toString() ?: null,
            'manual',
            $request->user(),
        );

        return StockMovementResource::make($movement->load('product'));
    }
}
