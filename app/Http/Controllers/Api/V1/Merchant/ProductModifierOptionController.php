<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Merchant\StoreProductModifierOptionRequest;
use App\Http\Requests\Api\V1\Merchant\UpdateProductModifierOptionRequest;
use App\Http\Resources\MerchantProductModifierOptionResource;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductModifierOptionController extends Controller
{
    public function store(
        StoreProductModifierOptionRequest $request,
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
                $context =
                    $this->resolveContext(
                        $request,
                        $product,
                        $modifier,
                        true
                    );

                if (
                    $context
                    instanceof \Illuminate\Http\JsonResponse
                ) {
                    return $context;
                }

                $data =
                    $request->validated();

                $option =
                    new ProductModifierOption();

                $option->id =
                    Str::uuid()
                        ->toString();

                $option->modifier_group_id =
                    $context[
                        'group'
                    ]->id;

                $option->name =
                    $data['name'];

                $option->price_delta =
                    $data[
                        'price_delta'
                    ] ?? 0;

                $option->sort_order =
                    $data[
                        'sort_order'
                    ] ?? 0;

                $option->is_active =
                    $data[
                        'is_active'
                    ] ?? true;

                $option->save();

                return response()->json([
                    'success' =>
                        true,

                    'message' =>
                        'Pilihan berhasil ditambahkan.',

                    'data' =>
                        new MerchantProductModifierOptionResource(
                            $option
                        ),
                ], 201);
            }
        );
    }

    public function update(
        UpdateProductModifierOptionRequest $request,
        string $product,
        string $modifier,
        string $option
    ) {
        return DB::transaction(
            function () use (
                $request,
                $product,
                $modifier,
                $option
            ) {
                /*
                 * Lock order:
                 *
                 * Product
                 * -> Modifier Group
                 * -> Modifier Option
                 */
                $context =
                    $this->resolveContext(
                        $request,
                        $product,
                        $modifier,
                        true
                    );

                if (
                    $context
                    instanceof \Illuminate\Http\JsonResponse
                ) {
                    return $context;
                }

                $modifierOption =
                    ProductModifierOption::query()
                        ->where(
                            'modifier_group_id',
                            $context[
                                'group'
                            ]->id
                        )
                        ->whereKey(
                            $option
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$modifierOption) {
                    return response()->json([
                        'success' =>
                            false,

                        'error' => [
                            'code' =>
                                'MODIFIER_OPTION_NOT_FOUND',

                            'message' =>
                                'Pilihan tidak ditemukan.',
                        ],
                    ], 404);
                }

                $data =
                    $request->validated();

                foreach (
                    [
                        'name',
                        'price_delta',
                        'sort_order',
                        'is_active',
                    ] as $field
                ) {
                    if (
                        array_key_exists(
                            $field,
                            $data
                        )
                    ) {
                        $modifierOption
                            ->{$field} =
                            $data[$field];
                    }
                }

                $modifierOption->save();

                return response()->json([
                    'success' =>
                        true,

                    'message' =>
                        'Pilihan berhasil diperbarui.',

                    'data' =>
                        new MerchantProductModifierOptionResource(
                            $modifierOption
                        ),
                ]);
            }
        );
    }

    public function destroy(
        Request $request,
        string $product,
        string $modifier,
        string $option
    ) {
        return DB::transaction(
            function () use (
                $request,
                $product,
                $modifier,
                $option
            ) {
                $context =
                    $this->resolveContext(
                        $request,
                        $product,
                        $modifier,
                        true
                    );

                if (
                    $context
                    instanceof \Illuminate\Http\JsonResponse
                ) {
                    return $context;
                }

                $modifierOption =
                    ProductModifierOption::query()
                        ->where(
                            'modifier_group_id',
                            $context[
                                'group'
                            ]->id
                        )
                        ->whereKey(
                            $option
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$modifierOption) {
                    return response()->json([
                        'success' =>
                            false,

                        'error' => [
                            'code' =>
                                'MODIFIER_OPTION_NOT_FOUND',

                            'message' =>
                                'Pilihan tidak ditemukan.',
                        ],
                    ], 404);
                }

                $modifierOption->delete();

                return response()->json([
                    'success' =>
                        true,

                    'message' =>
                        'Pilihan berhasil dihapus.',
                ]);
            }
        );
    }

    private function resolveContext(
        Request $request,
        string $product,
        string $modifier,
        bool $lockForUpdate = false
    ): array|\Illuminate\Http\JsonResponse {
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

        $groupQuery =
            ProductModifierGroup::query()
                ->where(
                    'product_id',
                    $merchantProduct->id
                )
                ->whereKey(
                    $modifier
                );

        if ($lockForUpdate) {
            $groupQuery
                ->lockForUpdate();
        }

        $group =
            $groupQuery->first();

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

        return [
            'product' =>
                $merchantProduct,

            'group' =>
                $group,
        ];
    }
}
