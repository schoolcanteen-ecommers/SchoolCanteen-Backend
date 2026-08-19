<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PublicCatalogReadService
{
    private const HOME_PRODUCTS_PER_TYPE = 8;

    /**
     * Landing-page read model.
     *
     * Hanya mengembalikan maksimal 8 produk terbaru
     * per merchant type dalam satu SQL query.
     */
    public function home(): array
    {
        $rankedProducts = DB::table(
            'products as p'
        )
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
            ->whereIn(
                'm.type',
                [
                    'canteen',
                    'cooperative',
                ]
            )
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'p.slug as product_slug',
                'p.description as product_description',
                'p.price as product_price',
                'p.stock as product_stock',
                'p.image_url as product_image_url',
                'p.is_active as product_is_active',
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
                'ROW_NUMBER() OVER (
                    PARTITION BY m.type
                    ORDER BY p.created_at DESC
                ) AS product_rank'
            );

        $rows = DB::query()
            ->fromSub(
                $rankedProducts,
                'ranked_products'
            )
            ->where(
                'product_rank',
                '<=',
                self::HOME_PRODUCTS_PER_TYPE
            )
            ->orderBy(
                'merchant_type'
            )
            ->orderByDesc(
                'product_created_at'
            )
            ->get();

        $data = [
            'canteen' => [
                'products' => [],
            ],

            'cooperative' => [
                'products' => [],
            ],
        ];

        foreach ($rows as $row) {
            $type =
                $row->merchant_type;

            if (!isset($data[$type])) {
                continue;
            }

            $data[$type]['products'][] =
                $this->mapProduct(
                    $row
                );
        }

        return $data;
    }

    /**
     * Full public browsing read model.
     *
     * Satu merchant type per request:
     * canteen / cooperative.
     *
     * Merchant tanpa produk tetap dikembalikan.
     */
    public function catalog(
        string $type
    ): array {
        $rows = DB::table(
            'merchants as m'
        )
            ->leftJoin(
                'products as p',
                function ($join) {
                    $join
                        ->on(
                            'p.merchant_id',
                            '=',
                            'm.id'
                        )
                        ->where(
                            'p.is_active',
                            true
                        )
                        ->whereNull(
                            'p.deleted_at'
                        );
                }
            )
            ->leftJoin(
                'categories as c',
                'c.id',
                '=',
                'p.category_id'
            )
            ->where(
                'm.is_active',
                true
            )
            ->where(
                'm.type',
                $type
            )
            ->orderBy(
                'm.name'
            )
            ->orderByDesc(
                'p.created_at'
            )
            ->select([
                'm.id as merchant_id',
                'm.owner_user_id as merchant_owner_user_id',
                'm.name as merchant_name',
                'm.type as merchant_type',
                'm.description as merchant_description',
                'm.logo_url as merchant_logo_url',
                'm.is_active as merchant_is_active',
                'm.is_open as merchant_is_open',
                'm.created_at as merchant_created_at',

                'p.id as product_id',
                'p.name as product_name',
                'p.slug as product_slug',
                'p.description as product_description',
                'p.price as product_price',
                'p.stock as product_stock',
                'p.image_url as product_image_url',
                'p.is_active as product_is_active',
                'p.created_at as product_created_at',

                'c.id as category_id',
                'c.name as category_name',
                'c.slug as category_slug',
            ])
            ->get();

        $merchants = [];
        $products = [];
        $categories = [];

        foreach ($rows as $row) {
            $merchantId =
                $row->merchant_id;

            if (
                !isset(
                    $merchants[$merchantId]
                )
            ) {
                $merchants[$merchantId] = [
                    'id' =>
                        $merchantId,

                    'owner_user_id' =>
                        $row->merchant_owner_user_id,

                    'name' =>
                        $row->merchant_name,

                    'type' =>
                        $row->merchant_type,

                    'description' =>
                        $row->merchant_description,

                    'logo_url' =>
                        $row->merchant_logo_url,

                    'is_active' =>
                        $this->toBoolean(
                            $row->merchant_is_active
                        ),

                    'is_open' =>
                        $this->toBoolean(
                            $row->merchant_is_open
                        ),

                    'products_count' =>
                        0,

                    'created_at' =>
                        $this->toIsoString(
                            $row->merchant_created_at
                        ),
                ];
            }

            if (!$row->product_id) {
                continue;
            }

            $merchants[
                $merchantId
            ]['products_count']++;

            $products[] =
                $this->mapProduct(
                    $row
                );

            if (
                $row->category_id &&
                !isset(
                    $categories[
                        $row->category_id
                    ]
                )
            ) {
                $categories[
                    $row->category_id
                ] = [
                    'id' =>
                        $row->category_id,

                    'merchant_id' =>
                        $merchantId,

                    'name' =>
                        $row->category_name,

                    'slug' =>
                        $row->category_slug,
                ];
            }
        }

        return [
            'merchants' =>
                array_values(
                    $merchants
                ),

            'products' =>
                $products,

            'categories' =>
                array_values(
                    $categories
                ),
        ];
    }

    private function mapProduct(
        object $row
    ): array {
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
                $this->toBoolean(
                    $row->product_is_active
                ),

            'merchant' => [
                'id' =>
                    $row->merchant_id,

                'name' =>
                    $row->merchant_name,

                'type' =>
                    $row->merchant_type,

                'is_open' =>
                    $this->toBoolean(
                        $row->merchant_is_open
                    ),
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
                $this->toIsoString(
                    $row->product_created_at
                ),
        ];
    }

    private function toBoolean(
        mixed $value
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            $value,
            [
                1,
                '1',
                't',
                'true',
            ],
            true
        );
    }

    private function toIsoString(
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
