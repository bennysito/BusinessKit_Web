<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with('category')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($productQuery) use ($search): void {
                    $productQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $this->authorize('create', Product::class);

        $product = Product::query()->create([
            ...$request->validated(),
            'quantity_on_hand' => $request->integer('quantity_on_hand', 0),
            'reorder_level' => $request->integer('reorder_level', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return ProductResource::make($product->load('category'));
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return ProductResource::make($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return ProductResource::make($product->refresh()->load('category'));
    }

    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }

    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with('category')
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }
}
