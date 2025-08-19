<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Listing\CreateProductRequest;
use App\Http\Requests\Api\V1\Listing\UpdateProductPhotosRequest;
use App\Http\Requests\Api\V1\Listing\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Condition;
use App\Models\Product;
use App\Services\Api\V1\Listing\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            ActivityLogHelper::logProductPosted($product);


            return $this->successResponse($product, __('responses.product.success.create'));

        } catch (\Throwable $e) {
            return $this->errorResponse(
                __('responses.product.failed.create', ['message' => $e->getMessage()])
            );
        }
    }


    public function userProducts()
    {
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
            ->where('approval_status', 'approved')
            ->get();
        return $this->successResponse($products);
    }


    public function showSingle($brand, $slug)
    {
        $id = last(explode('-', $slug));

        $product = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('approval_status', 'approved')
            ->find($id);

        if (!$product) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($product);
    }

    public function search(Request $request)
    {
        $query = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('approval_status', 'approved');


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

    public function groupCategories(string $group)
    {
        $group = ucfirst(strtolower($group)); // "men" -> "Men"
        $cats = Category::query()
            ->where('group', $group)
            ->orderBy('id')
            ->get(['id', 'name']);  // keep it light

        return response()->json([
            'status' => 'success',
            'data' => ['categories' => $cats],
        ]);
    }

    public function groupBrands(string $group)
    {
        $group = ucfirst(strtolower($group));
        $cats = Category::query()
            ->where('group', $group)
            ->orderBy('id')
            ->get(['id', 'name']);

        $products = Product::select('id', 'brand_id')->where('approval_status', 'approved')
            ->whereIn('category_id', $cats->pluck('id')->toArray())->get();

        $brands = Brand::whereIn('id', $products->pluck('brand_id')->toArray())->get();

        return response()->json([
            'status' => 'success',
            'data' => ['brands' => $brands],
        ]);
    }

    public function newProductsFetchsss(Request $request, ?string $group = null, ?string $category = null)
    {
        // pagination + sort
        $perPage = max(1, min((int)$request->input('per_page', 24), 60));
        $sort = $request->input('sort', 'newest');

        // normalize slugs (fallback path mode)
        $groupNorm = $group ? ucfirst(strtolower($group)) : null;
        $categorySlug = $category ? strtolower($category) : null;

        // 1) Determine category IDs to filter by
        $categoryIds = [];

        // Prefer explicit category_id from query
        if ($request->filled('category_id')) {
            $categoryIds = [(int)$request->input('category_id')];
        } elseif ($groupNorm) {
            // Fallback: resolve by group and optional category slug -> DB name
            $catQ = \App\Models\Category::query()->where('group', $groupNorm);

            if ($categorySlug) {
                // map simple slugs to your DB names if needed
                $map = [
                    'top' => 'Tops',
                    'bottom' => 'Bottoms',
                    'coats-and-jackets' => 'Coats and jackets',
                ];
                $dbName = $map[$categorySlug] ?? ucfirst($categorySlug);
                $catQ->where('name', $dbName);
            }

            $cats = $catQ->get(['id']);
            if ($cats->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found',
                ], 404);
            }
            $categoryIds = $cats->pluck('id')->all();
        }
        // if neither category_id nor group given -> no category filter (returns all that match other filters)

        // 2) Build products query
        $q = \App\Models\Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('approval_status',  'approved');

        if (!empty($categoryIds)) {
            $q->whereIn('category_id', $categoryIds);
        }
        if ($request->filled('brand_id')) {
            $q->where('brand_id', (int)$request->input('brand_id'));
        }
        if ($request->filled('condition_id')) {
            $q->where('condition_id', (int)$request->input('condition_id'));
        }
        if ($request->filled('min_price')) {
            $q->where('price', '>=', (float)$request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $q->where('price', '<=', (float)$request->input('max_price'));
        }

        // sorting
        match ($sort) {
            'price_asc' => $q->orderBy('price', 'asc'),
            'price_desc' => $q->orderBy('price', 'desc'),
            'newest' => $q->orderBy('id', 'desc'),
            default => $q->latest(),
        };

        // 3) Paginate (frontend expects paginator shape under data)
        $products = $q->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
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

    public function newProductsFetch(Request $request, ?string $group = null, ?string $category = null)
    {
        // pagination + sort
        $perPage = max(1, min((int)$request->input('per_page', 8), 60));
        $sort = $request->input('sort', 'newest'); // newest|price_asc|price_desc

        // normalize slugs from path (slug mode)
        $groupNorm = $group ? ucfirst(strtolower($group)) : null; // Men|Women|Kids|Beauty
        $categorySlug = $category ? strtolower($category) : null;    // tops|bottoms|coats-and-jackets

        // 1) Determine category IDs
        $categoryIds = [];
        if ($request->filled('category_id')) {
            $categoryIds = [(int)$request->input('category_id')];
        } elseif ($groupNorm) {
            $catQ = Category::query()->where('group', $groupNorm);
            if ($categorySlug) {
                $map = [
                    'top' => 'Tops',
                    'tops' => 'Tops',
                    'bottom' => 'Bottoms',
                    'bottoms' => 'Bottoms',
                    'coats-and-jackets' => 'Coats and jackets',
                ];
                $dbName = $map[$categorySlug] ?? ucfirst($categorySlug);
                $catQ->where('name', $dbName);
            }
            $cats = $catQ->get(['id']);
            if ($cats->isEmpty() && $categorySlug) {
                return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
            }
            $categoryIds = $cats->pluck('id')->all();
        }

        // 2) Build base query
        $q = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->where('approval_status', 'approved');

        if (!empty($categoryIds)) {
            $q->whereIn('category_id', $categoryIds);
        }
        if ($request->filled('brand_id')) {
            $q->where('brand_id', (int)$request->input('brand_id'));
        }
        if ($request->filled('condition_id')) {
            $q->where('condition_id', (int)$request->input('condition_id'));
        }

        // Size filter: support size_id or size code "S|M|L|XL"
        if ($request->filled('size_id')) {
            $q->where('size_id', (int)$request->input('size_id'));
        } elseif ($request->filled('size')) {
            $val = strtolower($request->input('size'));
            $q->whereHas('size', fn($qq) => $qq->where('standard_size', $val));
        }

        // Price range
        if ($request->filled('min_price')) {
            $q->where('price', '>=', (float)$request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $q->where('price', '<=', (float)$request->input('max_price'));
        }

        // sorting
        match ($sort) {
            'price_asc' => $q->orderBy('price', 'asc'),
            'price_desc' => $q->orderBy('price', 'desc'),
            'newest' => $q->orderBy('id', 'desc'),
            default => $q->latest(),
        };

        // Paginate
        $products = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    // ------------- Filter sources -------------

    public function listCategoriesByGroup(string $group)
    {
        $groupNorm = ucfirst(strtolower($group));
        $cats = Category::where('group', $groupNorm)->orderBy('id')->get(['id', 'name']);
        return response()->json(['status' => 'success', 'data' => ['categories' => $cats]]);
    }

    public function listBrandsByGroup(string $group)
    {
        $groupNorm = ucfirst(strtolower($group));
        // Either: brands by products in that group OR all brands; here: by group through categories
        $brandIds = Product::whereIn('category_id', Category::where('group', $groupNorm)->pluck('id'))
            ->distinct()->pluck('brand_id');
        $brands = Brand::whereIn('id', $brandIds)->orderBy('name')->get(['id', 'name']);
        return response()->json(['status' => 'success', 'data' => ['brands' => $brands]]);
    }

    public function listConditions()
    {
        $conds = Condition::orderBy('title')->get(['id', 'title']);
        return response()->json(['status' => 'success', 'data' => ['conditions' => $conds]]);
    }

    public function listSizes()
    {
        $raw = \App\Models\Size::query()
            ->whereNotNull('standard_size')
            ->distinct()
            ->pluck('standard_size');

        $map = [
            'extra_small' => ['code' => 'XS', 'title' => 'Extra Small'],
            'small' => ['code' => 'S', 'title' => 'Small'],
            'medium' => ['code' => 'M', 'title' => 'Medium'],
            'large' => ['code' => 'L', 'title' => 'Large'],
            'extra_large' => ['code' => 'XL', 'title' => 'Extra Large'],
            'xxl' => ['code' => 'XXL', 'title' => '2XL'],
        ];

        $sizes = [];
        foreach ($raw->values() as $i => $val) {
            $meta = $map[strtolower($val)] ?? [
                'code' => strtoupper(substr((string)$val, 0, 3)),
                'title' => ucwords(str_replace('_', ' ', (string)$val)),
            ];
            $sizes[] = [
                'id' => $i + 1,
                'value' => strtolower($val),
                'code' => $meta['code'],
                'title' => $meta['title'],
            ];
        }

        return response()->json(['status' => 'success', 'data' => ['sizes' => $sizes]]);
    }

    public function getMenProducts()
    {
        $products = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
        ->whereHas('category', function ($query) {
            $query->where('group', 'Men');
        })
            ->orderBy('created_at', 'desc')
            ->limit(8)

            ->get();

        return $products;
    }

    public function getWomenProducts()
    {
        $products = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->whereHas('category', function ($query) {
            $query->where('group', 'Women');
        })
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return $products;
    }

    public function getKidProducts()
    {
        $products = Product::with(['user', 'category', 'brand', 'condition', 'photos', 'address', 'size'])
            ->whereHas('category', function ($query) {
            $query->where('group', 'Kids');
        })
            ->orderBy('created_at', 'desc')
            ->limit(8)

            ->get();

        return $products;
    }

}
