<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Api\V1\Listing\ProductService;
use App\Http\Requests\Api\V1\Listing\CreateProductRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Store a new product along with up to 8 image uploads.
     *
     * @param CreateProductRequest $request
     * @return JsonResponse
     */
    public function store(CreateProductRequest $request)
    {
        // The CreateProductRequest automatically validates the input,
        // so we can safely retrieve the validated data.
        $validatedData = $request->validated();

        // Get images from the request if they exist.
        $images = $request->hasFile('images') ? $request->file('images') : null;

        try {
            $product = $this->productService->createProduct($validatedData, $images);

            return $this->successResponse($product, __('responses.product.success.create'));
        } catch (\Exception $e) {
            return $this->errorResponse(__('responses.product.failed.create', ['message' => $e->getMessage()]));
        }
    }

    public function show() {
        $user = auth()->user();
        $products = Product::where('user_id', $user->id)->with(['user', 'category', 'brand', 'condition', 'photos'])->get();
        return $this->successResponse($products);
    }

    /**
     * Return all products for public display.
     */
    public function publicProducts()
    {
        $products = Product::with(['user', 'category', 'brand', 'condition', 'photos'])->get();
        return $this->successResponse($products);
    }
}
