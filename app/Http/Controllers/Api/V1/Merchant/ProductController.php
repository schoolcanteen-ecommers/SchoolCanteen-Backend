<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Http\Requests\Api\V1\Merchant\UpdateProductRequest;
use App\Http\Requests\Api\V1\Merchant\StoreProductRequest;
use App\Http\Resources\MerchantProductResource;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $products = Product::query()
            ->where('merchant_id', $merchant->id)
            ->with('category')
            ->latest()
            ->paginate(20);

        return MerchantProductResource::collection($products)
            ->additional([
                'success' => true,
            ]);
    }

    public function store(
        StoreProductRequest $request,
        CloudinaryService $cloudinary
    ){
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Category Ownership
        |--------------------------------------------------------------------------
        */

        if (!empty($data['category_id'])) {
            $category = Category::query()
                ->whereKey($data['category_id'])
                ->where('merchant_id', $merchant->id)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CATEGORY',
                        'message' => 'Kategori tidak ditemukan atau bukan milik merchant.',
                    ],
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Unique Merchant Slug
        |--------------------------------------------------------------------------
        */

        $baseSlug = Str::slug($data['name']);

        if ($baseSlug === '') {
            $baseSlug = 'product';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::withTrashed()
                ->where('merchant_id', $merchant->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        /*
        |--------------------------------------------------------------------------
        | Product image upload to Cloudinary
        |--------------------------------------------------------------------------
        */
        $image = null;

        if ($request->hasFile('image')) {
            $image = $cloudinary->uploadImage(
                $request->file('image')->getPathname()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Product
        |--------------------------------------------------------------------------
        */

        $product = new Product();
        $product->image_url =
            $image['url'] ?? null;

        $product->image_public_id =
            $image['public_id'] ?? null;
        $product->id = Str::uuid()->toString();
        $product->merchant_id = $merchant->id;
        $product->category_id = $data['category_id'] ?? null;
        $product->name = $data['name'];
        $product->slug = $slug;
        $product->description = $data['description'] ?? null;
        $product->price = $data['price'];
        $product->stock = $data['stock'];
        $product->is_active = $data['is_active'] ?? true;

        try {
            $product->save();
        } catch (\Throwable $e) {
            if ($image) {
                $cloudinary->deleteImage(
                    $image['public_id']
                );
            }

            throw $e;
        }

        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => new MerchantProductResource($product),
        ], 201);
    }

    public function update(
        UpdateProductRequest $request,
        string $product,
        CloudinaryService $cloudinary
    ) {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Product Ownership
        |--------------------------------------------------------------------------
        */

        $merchantProduct = Product::query()
            ->where('merchant_id', $merchant->id)
            ->whereKey($product)
            ->first();

        if (!$merchantProduct) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Category Ownership
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists('category_id', $data)
            && $data['category_id'] !== null
        ) {
            $category = Category::query()
                ->whereKey($data['category_id'])
                ->where('merchant_id', $merchant->id)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CATEGORY',
                        'message' =>
                            'Kategori tidak ditemukan atau bukan milik merchant.',
                    ],
                ], 422);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Product image upload to Cloudinary
        |--------------------------------------------------------------------------
        */

        $newImage = null;
        $oldImagePublicId =
            $merchantProduct->image_public_id;

        if ($request->hasFile('image')) {
            $newImage = $cloudinary->uploadImage(
                $request->file('image')->getPathname()
            );

            $merchantProduct->image_url =
                $newImage['url'];

            $merchantProduct->image_public_id =
                $newImage['public_id'];
        }
        /*
        |--------------------------------------------------------------------------
        | Name + Slug
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists('name', $data)
            && $data['name'] !== $merchantProduct->name
        ) {
            $baseSlug = Str::slug($data['name']);

            if ($baseSlug === '') {
                $baseSlug = 'product';
            }

            $slug = $baseSlug;
            $counter = 2;

            while (
                Product::withTrashed()
                    ->where('merchant_id', $merchant->id)
                    ->where('id', '!=', $merchantProduct->id)
                    ->where('slug', $slug)
                    ->exists()
            ) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $merchantProduct->name = $data['name'];
            $merchantProduct->slug = $slug;
        }

        /*
        |--------------------------------------------------------------------------
        | Other Fields
        |--------------------------------------------------------------------------
        */

        if (array_key_exists('category_id', $data)) {
            $merchantProduct->category_id =
                $data['category_id'];
        }

        if (array_key_exists('description', $data)) {
            $merchantProduct->description =
                $data['description'];
        }

        if (array_key_exists('price', $data)) {
            $merchantProduct->price =
                $data['price'];
        }

        if (array_key_exists('stock', $data)) {
            $merchantProduct->stock =
                $data['stock'];
        }

        if (array_key_exists('is_active', $data)) {
            $merchantProduct->is_active =
                $data['is_active'];
        }

        try {
            $merchantProduct->save();
        } catch (\Throwable $e) {
            if ($newImage) {
                $cloudinary->deleteImage(
                    $newImage['public_id']
                );
            }

            throw $e;
        }

        if (
            $newImage
            && $oldImagePublicId
            && $oldImagePublicId
                !== $newImage['public_id']
        ) {
            try {
                $cloudinary->deleteImage(
                    $oldImagePublicId
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $merchantProduct->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' =>
                new MerchantProductResource($merchantProduct),
        ]);
    }

    public function destroy(
        Request $request,
        string $product
    ) {
        $profile = $request->attributes->get('profile');

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $merchantProduct = Product::query()
            ->where('merchant_id', $merchant->id)
            ->whereKey($product)
            ->first();

        if (!$merchantProduct) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        $merchantProduct->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

}