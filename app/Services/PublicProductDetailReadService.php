<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicProductDetailReadService
{
    private const RELATED_LIMIT = 4;

    public function detail(
        string $productId,
        bool $includeRelated = false
    ): ?array {
        $cacheKey =
            $this->cacheKey(
                $productId,
                $includeRelated
            );

        $cached =
            Cache::get(
                $cacheKey
            );

        if (is_array($cached)) {
            return $cached;
        }

        /*
        |--------------------------------------------------------------------------
        | Main Product
        |--------------------------------------------------------------------------
        |
        | Product + merchant + category + modifier group + modifier option
        | dibaca dalam SATU SQL query.
        |
        | LEFT JOIN pada modifier penting agar required group yang belum
        | memiliki option aktif tetap ikut terbaca.
        |
        */

        $rows =
            DB::table('products as p')
                ->join(
                    'merchants as m',
                    'm.id',
                    '=',
                    'p.merchant_id'
                )
                ->leftJoin(
                    'categories as c',
                    'c.id',
                    '=',
                    'p.category_id'
                )
                ->leftJoin(
                    'product_modifier_groups as pmg',
                    function ($join) {
                        $join
                            ->on(
                                'pmg.product_id',
                                '=',
                                'p.id'
                            )
                            ->where(
                                'pmg.is_active',
                                true
                            );
                    }
                )
                ->leftJoin(
                    'product_modifier_options as pmo',
                    function ($join) {
                        $join
                            ->on(
                                'pmo.modifier_group_id',
                                '=',
                                'pmg.id'
                            )
                            ->where(
                                'pmo.is_active',
                                true
                            );
                    }
                )
                ->where(
                    'p.id',
                    $productId
                )
                ->whereNull(
                    'p.deleted_at'
                )
                ->where(
                    'p.is_active',
                    true
                )
                ->where(
                    'm.is_active',
                    true
                )
                ->select([
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.slug as product_slug',
                    'p.description as product_description',
                    'p.price as product_price',
                    'p.stock as product_stock',
                    'p.image_url as product_image_url',
                    'p.created_at as product_created_at',

                    'm.id as merchant_id',
                    'm.name as merchant_name',
                    'm.type as merchant_type',
                    'm.is_open as merchant_is_open',

                    'c.id as category_id',
                    'c.name as category_name',
                    'c.slug as category_slug',

                    'pmg.id as modifier_group_id',
                    'pmg.name as modifier_group_name',
                    'pmg.selection_type as modifier_selection_type',
                    'pmg.is_required as modifier_is_required',
                    'pmg.min_select as modifier_min_select',
                    'pmg.max_select as modifier_max_select',
                    'pmg.sort_order as modifier_sort_order',

                    'pmo.id as modifier_option_id',
                    'pmo.name as modifier_option_name',
                    'pmo.price_delta as modifier_option_price_delta',
                    'pmo.sort_order as modifier_option_sort_order',
                ])
                ->orderBy(
                    'pmg.sort_order'
                )
                ->orderBy(
                    'pmg.id'
                )
                ->orderBy(
                    'pmo.sort_order'
                )
                ->orderBy(
                    'pmo.id'
                )
                ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $first =
            $rows->first();

        $modifierGroups = [];

        foreach ($rows as $row) {
            if (
                !$row->modifier_group_id
            ) {
                continue;
            }

            if (
                !array_key_exists(
                    $row->modifier_group_id,
                    $modifierGroups
                )
            ) {
                $modifierGroups[
                    $row->modifier_group_id
                ] = [
                    'id' =>
                        $row->modifier_group_id,

                    'name' =>
                        $row->modifier_group_name,

                    'selection_type' =>
                        $row->modifier_selection_type,

                    'is_required' =>
                        (bool)
                        $row->modifier_is_required,

                    'min_select' =>
                        (int)
                        $row->modifier_min_select,

                    'max_select' =>
                        (int)
                        $row->modifier_max_select,

                    'options' => [],
                ];
            }

            if (
                !$row->modifier_option_id
            ) {
                continue;
            }

            $modifierGroups[
                $row->modifier_group_id
            ]['options'][] = [
                'id' =>
                    $row->modifier_option_id,

                'name' =>
                    $row->modifier_option_name,

                'price_delta' =>
                    (int)
                    $row->modifier_option_price_delta,
            ];
        }

        $modifierGroups =
            array_values(
                $modifierGroups
            );

        /*
         * has_modifiers:
         * minimal satu active option usable.
         *
         * requires_customization:
         * minimal satu active required group,
         * walaupun option-nya sedang kosong.
         */
        $hasModifiers = false;
        $requiresCustomization = false;

        foreach (
            $modifierGroups
            as $group
        ) {
            if (
                count(
                    $group['options']
                ) > 0
            ) {
                $hasModifiers = true;
            }

            if (
                $group['is_required']
            ) {
                $requiresCustomization =
                    true;
            }
        }

        $data = [
            'id' =>
                $first->product_id,

            'name' =>
                $first->product_name,

            'slug' =>
                $first->product_slug,

            'description' =>
                $first->product_description,

            'price' =>
                (int)
                $first->product_price,

            'stock' =>
                (int)
                $first->product_stock,

            'image_url' =>
                $first->product_image_url,

            'is_active' => true,

            'has_modifiers' =>
                $hasModifiers,

            'requires_customization' =>
                $requiresCustomization,

            'merchant' => [
                'id' =>
                    $first->merchant_id,

                'name' =>
                    $first->merchant_name,

                'type' =>
                    $first->merchant_type,

                'is_open' =>
                    (bool)
                    $first->merchant_is_open,
            ],

            'category' =>
                $first->category_id
                    ? [
                        'id' =>
                            $first->category_id,

                        'name' =>
                            $first->category_name,

                        'slug' =>
                            $first->category_slug,
                    ]
                    : null,

            'modifier_groups' =>
                $modifierGroups,

            'created_at' =>
                $this->iso(
                    $first->product_created_at
                ),
        ];

        /*
         * Customization sheet tidak membutuhkan related products.
         *
         * Product Detail page meminta:
         * ?include_related=1
         */
        if ($includeRelated) {
            $data[
                'related_products'
            ] =
                $this->relatedProducts(
                    $first->merchant_id,
                    $first->product_id
                );
        }

        Cache::put(
            $cacheKey,
            $data,
            15
        );

        return $data;
    }

    private function relatedProducts(
        string $merchantId,
        string $currentProductId
    ): array {
        /*
         * Satu SQL query, limit hanya 4.
         *
         * Tidak lagi memanggil endpoint list
         * dengan paginate(12).
         */
        $rows =
            DB::table('products as p')
                ->join(
                    'merchants as m',
                    'm.id',
                    '=',
                    'p.merchant_id'
                )
                ->leftJoin(
                    'categories as c',
                    'c.id',
                    '=',
                    'p.category_id'
                )
                ->whereNull(
                    'p.deleted_at'
                )
                ->where(
                    'p.is_active',
                    true
                )
                ->where(
                    'm.is_active',
                    true
                )
                ->where(
                    'p.merchant_id',
                    $merchantId
                )
                ->where(
                    'p.id',
                    '!=',
                    $currentProductId
                )
                ->select([
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.slug as product_slug',
                    'p.description as product_description',
                    'p.price as product_price',
                    'p.stock as product_stock',
                    'p.image_url as product_image_url',
                    'p.created_at as product_created_at',

                    'm.id as merchant_id',
                    'm.name as merchant_name',
                    'm.type as merchant_type',
                    'm.is_open as merchant_is_open',

                    'c.id as category_id',
                    'c.name as category_name',
                    'c.slug as category_slug',
                ])
                ->selectRaw(
                    'EXISTS (
                        SELECT 1
                        FROM product_modifier_groups pmg
                        WHERE pmg.product_id = p.id
                          AND pmg.is_active = TRUE
                          AND EXISTS (
                              SELECT 1
                              FROM product_modifier_options pmo
                              WHERE pmo.modifier_group_id = pmg.id
                                AND pmo.is_active = TRUE
                          )
                    ) AS product_has_modifiers'
                )
                ->selectRaw(
                    'EXISTS (
                        SELECT 1
                        FROM product_modifier_groups pmg
                        WHERE pmg.product_id = p.id
                          AND pmg.is_active = TRUE
                          AND pmg.is_required = TRUE
                    ) AS product_requires_customization'
                )
                ->orderByDesc(
                    'p.created_at'
                )
                ->limit(
                    self::RELATED_LIMIT
                )
                ->get();

        return $rows
            ->map(
                function ($row) {
                    return [
                        'id' =>
                            $row->product_id,

                        'name' =>
                            $row->product_name,

                        'slug' =>
                            $row->product_slug,

                        'description' =>
                            $row->product_description,

                        'price' =>
                            (int)
                            $row->product_price,

                        'stock' =>
                            (int)
                            $row->product_stock,

                        'image_url' =>
                            $row->product_image_url,

                        'is_active' =>
                            true,

                        'has_modifiers' =>
                            (bool)
                            $row->product_has_modifiers,

                        'requires_customization' =>
                            (bool)
                            $row->product_requires_customization,

                        'merchant' => [
                            'id' =>
                                $row->merchant_id,

                            'name' =>
                                $row->merchant_name,

                            'type' =>
                                $row->merchant_type,

                            'is_open' =>
                                (bool)
                                $row->merchant_is_open,
                        ],

                        'category' =>
                            $row->category_id
                                ? [
                                    'id' =>
                                        $row->category_id,

                                    'name' =>
                                        $row->category_name,

                                    'slug' =>
                                        $row->category_slug,
                                ]
                                : null,

                        'created_at' =>
                            $this->iso(
                                $row->product_created_at
                            ),
                    ];
                }
            )
            ->values()
            ->all();
    }

    private function cacheKey(
        string $productId,
        bool $includeRelated
    ): string {
        return sprintf(
            'public-product-detail:v1:%s:%s',
            $productId,
            $includeRelated
                ? 'related'
                : 'light'
        );
    }

    private function iso(
        mixed $value
    ): ?string {
        if (!$value) {
            return null;
        }

        return Carbon::parse(
            (string) $value
        )->toISOString();
    }
}
