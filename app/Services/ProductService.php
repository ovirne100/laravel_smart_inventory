<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAll($filters = [])
    {
        $query = Product::with('categoria')
            ->search($filters['search'] ?? null)
            ->category($filters['category_id'] ?? null)
            ->status($filters['status'] ?? null);

        if (isset($filters['min_price']) && isset($filters['max_price'])) {
            $query->priceRange($filters['min_price'], $filters['max_price']);
        }

        return $query->paginate(10);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product;
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
