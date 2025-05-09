<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Listing\UpdateProductPhotosRequest;
use App\Http\Requests\Api\V1\Listing\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Services\Api\V1\Listing\ProductService;
use App\Http\Requests\Api\V1\Listing\CreateProductRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function store(CreateProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Validate address ownership
        if (!$user->addresses()->where('id', $validated['address_id'] ?? null)->exists()) {
            return $this->errorResponse(__('responses.product.failed.invalid_address'));
        }

        // Resolve or create brand
        $brand = Brand::firstOrCreate(['name' => $validated['brand_name']]);
        $validated['brand_id'] = $brand->id;
        unset($validated['brand_name']);

        // Prepare images if present
        $images = $request->file('images') ?? [];

        try {
            // Create product
            $product = $this->productService->createProduct($validated, $images);

            // Create associated size if provided
            if (!empty($validated['size_data'])) {
                $product->size()->create($validated['size_data']);
            }

            // Eager load the size relation
            $product->load('size');

            // Log product activity
            activity()
                ->performedOn($product)
                ->causedBy($user)
                ->withProperties(['data' => $product, 'message' => 'New product posted by a user. Tap to see.', 'title' => 'Product posted'])
                ->log('product_posted');

            return $this->successResponse($product, __('responses.product.success.create'));

        } catch (\Throwable $e) {
            return $this->errorResponse(
                __('responses.product.failed.create', ['message' => $e->getMessage()])
            );
        }
    }


    public function userProducts() {
        $user = auth()->user();
        $products = Product::where('user_id', $user->id)->with(['user', 'category', 'brand', 'condition', 'photos', 'size'])->get();
        return $this->successResponse($products);
    }

    public function showSingleAuth($id)
    {
        $user = auth()->user();
        $product = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$product) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($product);
    }

    /**
     * Return all products for public display.
     */
    public function publicProducts()
    {
        $products = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'size'])
            ->where('approval_status', '!=', 'pending')
            ->paginate(10);
        return $this->successResponse($products);
    }



    public function showSingle($id)
    {
        $product = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('approval_status', '!=', 'pending')
            ->find($id);

        if (!$product) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($product);
    }

    public function search(Request $request)
    {
        $query = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('approval_status', '!=', 'pending');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->get('brand_id'));
        }
        if ($request->filled('condition_id')) {
            $query->where('condition_id', $request->get('condition_id'));
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->get('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->get('max_price'));
        }
        if ($request->filled('city')) {
            $query->where('city', $request->get('city'));
        }
        if ($request->filled('location')) {
            $query->where('location', $request->get('location'));
        }
        // Additional filters can be added here as needed.

        // Pagination (default 10 per page, can be overridden with ?per_page=)
        $perPage = $request->get('per_page', 10);
        $products = $query->paginate($perPage);

        return $this->successResponse($products);
    }

    public function searchUserProducts(Request $request)
    {
        $user = auth()->user();
        $query = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('user_id', $user->id);

        // Apply similar filters as in the public search.
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->get('brand_id'));
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->get('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->get('max_price'));
        }
        // Additional filters can be added as required.

        $perPage = $request->get('per_page', 10);
        $products = $query->paginate($perPage);

        return $this->successResponse($products);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $product = Product::where('id', $id)->where('user_id', $user->id)->first();

        if (!$product) {
            return $this->notFoundResponse();
        }

        $product->delete();
        return $this->successResponse();
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $user = auth()->user();
        $product = Product::with(['photos', 'category', 'brand', 'condition', 'address', 'user', 'size'])
            ->where('id', $id)->where('user_id', $user->id)->first();

        if (!$product) {
            return $this->notFoundResponse();
        }

        $data = $request->validated();
        $product->update($data);

        return $this->successResponse($product, 'Product updated successfully');
    }

    public function updatePhotos(UpdateProductPhotosRequest $request, $id)
    {
        $user = auth()->user();

        $product = Product::with('photos')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$product) {
            return $this->notFoundResponse();
        }

        $data = $request->validated();

        // Delete photos
        if (!empty($data['delete_photo_ids'])) {
            $photosToDelete = $product->photos()->whereIn('id', $data['delete_photo_ids'])->get();

            foreach ($photosToDelete as $photo) {
                Storage::delete($photo->image_path);
                $photo->forceDelete(); // permanently remove
            }
        }

        // Upload new photos
        if ($request->hasFile('new_photos')) {
            foreach ($request->file('new_photos') as $uploadedPhoto) {
                if ($uploadedPhoto instanceof UploadedFile) {
                    $filename = time() . '_' . $uploadedPhoto->getClientOriginalName();

                    $relativePath = $uploadedPhoto->storeAs('products', $filename, 'public');
                    $fullUrl = asset(Storage::url($relativePath));

                    $product->photos()->create([
                        'image_path' => $fullUrl,
                        // Add other fields if needed
                    ]);
                }

            }
        }

        // Reload updated photo list
        $product->load('photos');

        return $this->successResponse($product, 'Product photos updated successfully');
    }


}
