<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Merchant\StoreProductModifierGroupRequest;
use App\Http\Requests\Api\V1\Merchant\UpdateProductModifierGroupRequest;
use App\Http\Resources\MerchantProductModifierResource;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductModifierGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductModifierGroupController extends Controller
{
    public function index(
        Request $request,
        string $product
    ) {
        $merchantProduct =
            $this->resolveMerchantProduct(
                $request,
                $product
            );

        if (
            $merchantProduct
            instanceof \Illuminate\Http\JsonResponse
        ) {
            return $merchantProduct;
        }

        $groups =
            $merchantProduct
                ->modifierGroups()
                ->with('options')
                ->get();

        return MerchantProductModifierResource::collection(
            $groups
        )->additional([
            'success' => true,
        ]);
    }

    public function store(
        StoreProductModifierGroupRequest $request,
        string $product
    ) {
        return DB::transaction(
            function () use (
                $request,
                $product
            ) {
                /*
                 * Product lock menjadi serialization
                 * boundary antara checkout student
                 * dan perubahan modifier merchant.
                 */
                $merchantProduct =
                    $this->resolveMerchantProduct(
                        $request,
                        $product,
                        true
                    );

                if (
                    $merchantProduct
                    instanceof \Illuminate\Http\JsonResponse
                ) {
                    return $merchantProduct;
                }

                $data =
                    $request->validated();

                $config =
                    $this->normalizeSelectionConfig(
                        $data['selection_type'],
                        (bool)
                            $data['is_required'],
                        $data['min_select']
                            ?? null,
                        $data['max_select']
                            ?? null
                    );

                $group =
                    new ProductModifierGroup();

                $group->id =
                    Str::uuid()
                        ->toString();

                $group->product_id =
                    $merchantProduct->id;

                $group->name =
                    $data['name'];

                $group->selection_type =
                    $data['selection_type'];

                $group->is_required =
                    (bool)
                    $data['is_required'];

                $group->min_select =
                    $config['min_select'];

                $group->max_select =
                    $config['max_select'];

                $group->sort_order =
                    $data['sort_order']
                        ?? 0;

                $group->is_active =
                    $data['is_active']
                        ?? true;

                $group->save();

                $group->load(
                    'options'
                );

                return response()->json([
                    'success' =>
                        true,

                    'message' =>
                        'Grup pilihan berhasil ditambahkan.',

                    'data' =>
                        new MerchantProductModifierResource(
                            $group
                        ),
                ], 201);
            }
        );
    }

    public function update(
        UpdateProductModifierGroupRequest $request,
        string $product,
        string $modifier
    ) {
        return DB::transaction(
            function () use (
                $request,
                $product,
                $modifier
            ) {
                /*
                 * Lock order:
                 *
                 * Product
                 * -> Modifier Group
                 */
                $merchantProduct =
                    $this->resolveMerchantProduct(
                        $request,
                        $product,
                        true
                    );

                if (
                    $merchantProduct
                    instanceof \Illuminate\Http\JsonResponse
                ) {
                    return $merchantProduct;
                }

                $group =
                    ProductModifierGroup::query()
                        ->where(
                            'product_id',
                            $merchantProduct->id
                        )
                        ->whereKey(
                            $modifier
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$group) {
                    return response()->json([
                        'success' =>
                            false,

                        'error' => [
                            'code' =>
                                'MODIFIER_GROUP_NOT_FOUND',

                            'message' =>
                                'Grup pilihan tidak ditemukan.',
                        ],
                    ], 404);
                }

                $data =
                    $request->validated();

                $selectionType =
                    $data['selection_type']
                        ?? $group
                            ->selection_type;

                $isRequired =
                    array_key_exists(
                        'is_required',
                        $data
                    )
                        ? (bool)
                            $data[
                                'is_required'
                            ]
                        : (bool)
                            $group
                                ->is_required;

                $minSelect =
                    array_key_exists(
                        'min_select',
                        $data
                    )
                        ? $data[
                            'min_select'
                        ]
                        : $group
                            ->min_select;

                $maxSelect =
                    array_key_exists(
                        'max_select',
                        $data
                    )
                        ? $data[
                            'max_select'
                        ]
                        : $group
                            ->max_select;

                $config =
                    $this->normalizeSelectionConfig(
                        $selectionType,
                        $isRequired,
                        $minSelect,
                        $maxSelect
                    );

                if (
                    array_key_exists(
                        'name',
                        $data
                    )
                ) {
                    $group->name =
                        $data['name'];
                }

                $group->selection_type =
                    $selectionType;

                $group->is_required =
                    $isRequired;

                $group->min_select =
                    $config['min_select'];

                $group->max_select =
                    $config['max_select'];

                if (
                    array_key_exists(
                        'sort_order',
                        $data
                    )
                ) {
                    $group->sort_order =
                        $data[
                            'sort_order'
                        ];
                }

                if (
                    array_key_exists(
                        'is_active',
                        $data
                    )
                ) {
                    $group->is_active =
                        $data[
                            'is_active'
                        ];
                }

                $group->save();

                $group->load(
                    'options'
                );

                return response()->json([
                    'success' =>
                        true,

                    'message' =>
                        'Grup pilihan berhasil diperbarui.',

                    'data' =>
                        new MerchantProductModifierResource(
                            $group
                        ),
                ]);
            }
        );
    }

    public function destroy(
        Request $request,
        string $product,
        string $modifier
    ) {
        return DB::transaction(
            function () use (
                $request,
                $product,
                $modifier
            ) {
                $merchantProduct =
                    $this->resolveMerchantProduct(
                        $request,
                        $product,
                        true
                    );

                if (
                    $merchantProduct
                    instanceof \Illuminate\Http\JsonResponse
                ) {
                    return $merchantProduct;
                }

                $group =
                    ProductModifierGroup::query()
                        ->where(
                            'product_id',
                            $merchantProduct->id
                        )
                        ->whereKey(
                            $modifier
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$group) {
                    return response()->json([
                        'success' =>
                            false,

                        'error' => [
                            'code' =>
                                'MODIFIER_GROUP_NOT_FOUND',

                            'message' =>
                                'Grup pilihan tidak ditemukan.',
                        ],
                    ], 404);
                }

                $group->delete();

                return response()->json([
                    'success' =>
                        true,

                    'message' =>
                        'Grup pilihan berhasil dihapus.',
                ]);
            }
        );
    }

    private function resolveMerchantProduct(
        Request $request,
        string $product,
        bool $lockForUpdate = false
    ): Product|\Illuminate\Http\JsonResponse {
        $profile =
            $request->attributes->get(
                'profile'
            );

        $merchant =
            Merchant::query()
                ->where(
                    'owner_user_id',
                    $profile->id
                )
                ->first();

        if (!$merchant) {
            return response()->json([
                'success' =>
                    false,

                'error' => [
                    'code' =>
                        'MERCHANT_NOT_FOUND',

                    'message' =>
                        'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $productQuery =
            Product::query()
                ->where(
                    'merchant_id',
                    $merchant->id
                )
                ->whereKey(
                    $product
                );

        if ($lockForUpdate) {
            $productQuery
                ->lockForUpdate();
        }

        $merchantProduct =
            $productQuery->first();

        if (!$merchantProduct) {
            return response()->json([
                'success' =>
                    false,

                'error' => [
                    'code' =>
                        'PRODUCT_NOT_FOUND',

                    'message' =>
                        'Produk tidak ditemukan.',
                ],
            ], 404);
        }

        return $merchantProduct;
    }

    private function normalizeSelectionConfig(
        string $selectionType,
        bool $isRequired,
        ?int $minSelect,
        ?int $maxSelect
    ): array {
        if (
            $selectionType
            === 'single'
        ) {
            return [
                'min_select' =>
                    $isRequired
                        ? 1
                        : 0,

                'max_select' =>
                    1,
            ];
        }

        $normalizedMin =
            $isRequired
                ? max(
                    1,
                    (int)
                    ($minSelect ?? 1)
                )
                : 0;

        $normalizedMax =
            max(
                $normalizedMin,
                (int)
                ($maxSelect ?? 1),
                1
            );

        return [
            'min_select' =>
                $normalizedMin,

            'max_select' =>
                $normalizedMax,
        ];
    }
}
