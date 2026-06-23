<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductCategoryRequest;
use App\Http\Requests\Inventory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($categoryQuery) use ($search): void {
                    $categoryQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductCategoryResource::collection($categories);
    }

    public function store(StoreProductCategoryRequest $request): ProductCategoryResource
    {
        $this->authorize('create', ProductCategory::class);

        $category = ProductCategory::query()->create($request->validated());

        return ProductCategoryResource::make($category);
    }

    public function show(ProductCategory $category): ProductCategoryResource
    {
        $this->authorize('view', $category);

        return ProductCategoryResource::make($category);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): ProductCategoryResource
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return ProductCategoryResource::make($category->refresh());
    }

    public function destroy(ProductCategory $category): Response
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }
}
