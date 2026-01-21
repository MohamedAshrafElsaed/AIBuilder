<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles product business logic and data operations.
 *
 * @package App\Services
 */
class ProductService
{
    /**
     * Get all products with optional filters and pagination.
     *
     * @param array $filters Available filters: 'search', 'category_id', 'min_price', 'max_price'
     * @param array $pagination Pagination options: 'per_page', 'page'
     * @return LengthAwarePaginator
     */
    public function getAllProducts(array $filters = [], array $pagination = []): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply price range filters
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $allowedSortFields = ['name', 'price', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc');
        }

        // Apply pagination
        $perPage = $pagination['per_page'] ?? 15;
        $perPage = is_numeric($perPage) && $perPage > 0 && $perPage <= 100 ? (int) $perPage : 15;

        return $query->paginate($perPage);
    }

    /**
     * Get a single product by ID with category relationship.
     *
     * @param int $id
     * @return Product
     * @throws ModelNotFoundException
     */
    public function getProductById(int $id): Product
    {
        try {
            return Product::with('category')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning('Product not found', ['product_id' => $id]);
            throw $e;
        }
    }

    /**
     * Create a new product.
     *
     * @param array $data Product data: 'name', 'description', 'price', 'category_id'
     * @return Product
     * @throws \Exception
     */
    public function createProduct(array $data): Product
    {
        try {
            DB::beginTransaction();

            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'category_id' => $data['category_id'] ?? null,
            ]);

            // Load the category relationship
            $product->load('category');

            DB::commit();

            Log::info('Product created successfully', [
                'product_id' => $product->id,
                'name' => $product->name,
            ]);

            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array $data Product data to update
     * @return Product
     * @throws \Exception
     */
    public function updateProduct(Product $product, array $data): Product
    {
        try {
            DB::beginTransaction();

            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['price'])) {
                $updateData['price'] = $data['price'];
            }

            if (isset($data['category_id'])) {
                $updateData['category_id'] = $data['category_id'];
            }

            $product->update($updateData);

            // Reload the category relationship
            $product->load('category');

            DB::commit();

            Log::info('Product updated successfully', [
                'product_id' => $product->id,
                'updated_fields' => array_keys($updateData),
            ]);

            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Delete a product.
     *
     * @param Product $product
     * @return bool
     * @throws \Exception
     */
    public function deleteProduct(Product $product): bool
    {
        try {
            DB::beginTransaction();
            
            $productId = $product->id;
            $productName = $product->name;

            $deleted = $product->delete();

            DB::commit();

            if ($deleted) {
                Log::info('Product deleted successfully', [
                    'product_id' => $productId,
                    'name' => $productName,
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get products by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProductsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with('category')
            ->where('category_id', $categoryId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
