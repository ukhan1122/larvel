<?php

namespace App\Services\Api\V1\Listing;

use App\Models\Product;
use App\Repositories\V1\Eloquent\ProductRepository;
use App\Repositories\V1\Eloquent\PhotoRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    protected $productRepository;
    protected $photoRepository;

    public function __construct(ProductRepository $productRepository, PhotoRepository $photoRepository)
    {
        $this->productRepository = $productRepository;
        $this->photoRepository   = $photoRepository;
    }

    /**
     * Create a product and handle image uploads.
     *
     * @param array $data      Validated product data.
     * @param array|null $images Array of UploadedFile instances.
     * @return Product
     */
    public function createProduct(array $data, ?array $images = null)
    {
        return DB::transaction(function () use ($data, $images) {
            // Create the product record.
            $product = $this->productRepository->create($data);

            // Process each image file if provided.
            if ($images) {
                foreach ($images as $image) {
                    if ($image instanceof UploadedFile) {
                        // Generate a unique filename.
                        $filename = time() . '_' . $image->getClientOriginalName();
                        // Store the image in the 'products' folder on the public disk.
                        $relativePath = $image->storeAs('products', $filename, 'public');

                        $fullUrl = asset(Storage::url($relativePath));

                        // Save the photo record linked to the product.
                        $this->photoRepository->create([
                            'product_id' => $product->id,
                            'image_path' => $fullUrl,
                        ]);
                    }
                }
            }

            // Return the product with its associated photos.
            return $product->load('photos', 'category', 'user', 'brand', 'condition', 'address');
        });
    }
}
