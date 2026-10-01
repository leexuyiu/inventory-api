<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $products = Product::query()
            ->with(['category', 'suppliers'])
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price', '<=', $filters['max_price']))
            ->stockLevel($filters['stock_level'] ?? null)
            ->latest()
            ->paginate($filters['per_page'] ?? 15);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $validated = $request->validated();
        $supplierIds = $validated['supplier_ids'] ?? [];
        unset($validated['supplier_ids']);

        $product = Product::create($validated);
        $product->suppliers()->sync($supplierIds);

        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $validated = $request->validated();
        $supplierIds = $validated['supplier_ids'] ?? null;
        unset($validated['supplier_ids']);

        $product->update($validated);

        if ($supplierIds !== null) {
            $product->suppliers()->sync($supplierIds);
        }

        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
