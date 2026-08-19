<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResolveProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\PublicProductDetailReadService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        private readonly
        PublicProductDetailReadService $productDetail
    ) {
    }

    public function index(Request $request)
    {
        $products =
            $this->withModifierFlags(
                Product::query()
                    ->with([
                        'merchant',
                        'category',
                    ])
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

                ->when(
                    $request->filled(
                        'search'
                    ),
                    function ($query) use ($request) {
                        $search =
                            $request
                                ->string(
                                    'search'
                                )
                                ->toString();

                        $query->where(
                            function ($query) use ($search) {
                                $query
                                    ->where(
                                        'name',
                                        'ilike',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'description',
                                        'ilike',
                                        "%{$search}%"
                                    );
                            }
                        );
                    }
                )

                ->when(
                    $request->filled(
                        'merchant_id'
                    ),
                    fn ($query) =>
                        $query->where(
                            'merchant_id',
                            $request->merchant_id
                        )
                )

                ->when(
                    $request->filled(
                        'category_id'
                    ),
                    fn ($query) =>
                        $query->where(
                            'category_id',
                            $request->category_id
                        )
                )

                ->when(
                    $request->filled(
                        'merchant_type'
                    ),
                    function ($query) use ($request) {
                        $query->whereHas(
                            'merchant',
                            function ($merchantQuery) use ($request) {
                                $merchantQuery
                                    ->where(
                                        'type',
                                        $request->merchant_type
                                    );
                            }
                        );
                    }
                )

                ->latest()
                ->paginate(12);

        return ProductResource::collection(
            $products
        );
    }

    public function resolve(
        ResolveProductsRequest $request
    ) {
        $requestedIds =
            collect(
                $request->validated(
                    'product_ids'
                )
            )->values();

        $validUuidIds =
            $requestedIds
                ->filter(
                    fn ($id) =>
                        Str::isUuid($id)
                )
                ->values();

        $products =
            $this->withModifierFlags(
                Product::query()
                    ->with([
                        'merchant',
                        'category',
                    ])
            )
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
                                (
                                    new ProductResource(
                                        $product
                                    )
                                )->resolve(
                                    $request
                                )
                        )
                        ->values(),

                'unavailable_product_ids' =>
                    $unavailableProductIds,
            ],
        ]);
    }

    public function show(
        Request $request,
        string $product
    ) {
        $detail =
            $this
                ->productDetail
                ->detail(
                    $product,
                    $request->boolean(
                        'include_related'
                    )
                );

        if (!$detail) {
            return response()->json([
                'success' => false,

                'error' => [
                    'code' =>
                        'PRODUCT_NOT_FOUND',

                    'message' =>
                        'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        /*
         * Bentuk envelope tetap kompatibel
         * dengan apiRequest<T>() frontend.
         */
        return response()->json([
            'data' =>
                $detail,
        ]);
    }

    private function withModifierFlags(
        Builder $query
    ): Builder {
        return $query->withExists([
            /*
             * Sebuah modifier dianggap usable
             * hanya ketika mempunyai minimal
             * satu option aktif.
             */
            'modifierGroups as has_modifiers' =>
                function ($query) {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->whereHas(
                            'options',
                            fn ($optionQuery) =>
                                $optionQuery
                                    ->where(
                                        'is_active',
                                        true
                                    )
                        );
                },

            'modifierGroups as requires_customization' =>
                function ($query) {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'is_required',
                            true
                        );
                },
        ]);
    }
}
