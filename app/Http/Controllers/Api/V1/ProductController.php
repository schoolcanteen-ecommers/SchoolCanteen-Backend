<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

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
