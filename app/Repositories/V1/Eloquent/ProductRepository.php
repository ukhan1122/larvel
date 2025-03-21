<?php

namespace App\Repositories\V1\Eloquent;

use App\Models\Product;

class ProductRepository
{
    /**
     * Create a new product record.
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product
    {
        $user = auth()->user();
        $data['user_id'] = $user->id;
        $product = Product::create($data);
        $product->refresh();
        return $product;
    }
}
