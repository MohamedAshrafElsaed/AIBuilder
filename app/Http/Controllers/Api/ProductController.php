<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles API operations for products.
 *
 * @package App\Http\Controllers\Api
 */
class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param ProductService $productService
     */
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a paginated listing of products with their categories.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $products = Product::with('category')
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('min_price'), function ($query) use ($request) {
                $query->where('price', '>=', $request->input('min_price'));
            })
            ->when($request->filled('max_price'), function ($query) use ($request) {
                $query->where('price', '<=', $request->input('max_price'));
            })
            ->orderBy(
                $request->input('sort_by', 'created_at'),
                $request->input('sort_order', 'desc')
            )
            ->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display the specified product with its category.
     *
     * @param Product $product
     * @return ProductResource
     */
    public function show(Product $product): ProductResource
    {
        $product->load('category');

        return new ProductResource($product);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        $product->load('category');

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified product in storage.
     *
     * @param UpdateProductRequest $request
     * @param Product $product
     * @return ProductResource
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $updatedProduct = $this->productService->updateProduct($product, $request->validated());

        $updatedProduct->load('category');

        return new ProductResource($updatedProduct);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product);

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }
}
