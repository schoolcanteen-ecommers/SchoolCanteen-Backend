<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResolveProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->with([
                'merchant',
                'category',
            ])
            ->where('is_active', true)
            ->whereHas('merchant', function ($query) {
                $query->where('is_active', true);
            })

            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('description', 'ilike', "%{$search}%");
                    });
                }
            )

            ->when(
                $request->filled('merchant_id'),
                fn($query) =>
                $query->where(
                    'merchant_id',
                    $request->merchant_id
                )
            )

            ->when(
                $request->filled('category_id'),
                fn($query) =>
                $query->where(
                    'category_id',
                    $request->category_id
                )
            )

            ->when(
                $request->filled('merchant_type'),
                function ($query) use ($request) {
                    $query->whereHas(
                        'merchant',
                        function ($merchantQuery) use ($request) {
                            $merchantQuery->where(
                                'type',
                                $request->merchant_type
                            );
                        }
                    );
                }
            )

            ->latest()
            ->paginate(12);

        return ProductResource::collection($products);
    }

    public function resolve(ResolveProductsRequest $request)
    {
        $requestedIds = collect(
            $request->validated('product_ids')
        )->values();

        $validUuidIds = $requestedIds
            ->filter(
                fn ($id) => Str::isUuid($id)
            )
            ->values();

        $products = Product::query()
            ->with([
                'merchant',
                'category',
            ])
            ->whereIn(
                'id',
                $validUuidIds
            )
            ->where(
                'is_active',
                true
            )
            ->whereHas(
                'merchant',
                function ($query) {
                    $query->where(
                        'is_active',
                        true
                    );
                }
            )
            ->get()
            ->keyBy('id');

        $resolvedProducts =
            $requestedIds
                ->map(
                    fn ($id) =>
                    $products->get($id)
                )
                ->filter()
                ->values();

        $unavailableProductIds =
            $requestedIds
                ->reject(
                    fn ($id) =>
                    $products->has($id)
                )
                ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'products' =>
                    $resolvedProducts
                        ->map(
                            fn (Product $product) =>
                            (new ProductResource(
                                $product
                            ))->resolve($request)
                        )
                        ->values(),

                'unavailable_product_ids' =>
                    $unavailableProductIds,
            ],
        ]);
    }

    public function show(Product $product)
    {
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => 'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        $product->load([
            'merchant',
            'category',
        ]);

        return new ProductResource($product);
    }
}
