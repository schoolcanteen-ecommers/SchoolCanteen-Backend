<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    private const CANTEEN_MAIN =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const CANTEEN_EMPTY =
        'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const CANTEEN_INACTIVE =
        'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const COOPERATIVE_MAIN =
        'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

    private const CANTEEN_CATEGORY =
        '11111111-aaaa-4111-8111-111111111111';

    private const COOPERATIVE_CATEGORY =
        '22222222-bbbb-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->createModifierSchema();
        $this->seedTestData();
    }

    private function createTestSchema(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('merchants');

        Schema::create(
            'merchants',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('owner_user_id');

                $table->string('name');
                $table->string('type');

                $table->text('description')
                    ->nullable();

                $table->text('logo_url')
                    ->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->boolean('is_open')
                    ->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'categories',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('merchant_id');

                $table->string('name');
                $table->string('slug');

                $table->timestamps();
            }
        );

        Schema::create(
            'products',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('merchant_id');

                $table->uuid('category_id')
                    ->nullable();

                $table->string('name');
                $table->string('slug');

                $table->text('description')
                    ->nullable();

                $table->unsignedBigInteger(
                    'price'
                );

                $table->unsignedInteger(
                    'stock'
                );

                $table->text('image_url')
                    ->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->softDeletes();
                $table->timestamps();
            }
        );
    }

    private function createModifierSchema(): void
    {
        Schema::dropIfExists(
            'product_modifier_options'
        );

        Schema::dropIfExists(
            'product_modifier_groups'
        );

        Schema::create(
            'product_modifier_groups',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'product_id'
                );

                $table->string(
                    'name',
                    100
                );

                $table->string(
                    'selection_type',
                    20
                )->default('single');

                $table->boolean(
                    'is_required'
                )->default(false);

                $table->unsignedSmallInteger(
                    'min_select'
                )->default(0);

                $table->unsignedSmallInteger(
                    'max_select'
                )->default(1);

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(0);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'product_modifier_options',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'modifier_group_id'
                );

                $table->string(
                    'name',
                    100
                );

                $table->unsignedBigInteger(
                    'price_delta'
                )->default(0);

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(0);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
        );
    }

    private function seedTestData(): void
    {
        DB::table('merchants')->insert([
            [
                'id' =>
                    self::CANTEEN_MAIN,

                'owner_user_id' =>
                    'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa',

                'name' =>
                    'Kantin Utama',

                'type' =>
                    'canteen',

                'description' =>
                    'Kantin utama sekolah.',

                'logo_url' =>
                    null,

                'is_active' =>
                    true,

                'is_open' =>
                    true,
            ],

            [
                'id' =>
                    self::CANTEEN_EMPTY,

                'owner_user_id' =>
                    'bbbbbbbb-2222-4222-8222-bbbbbbbbbbbb',

                'name' =>
                    'Kantin Tanpa Produk',

                'type' =>
                    'canteen',

                'description' =>
                    null,

                'logo_url' =>
                    null,

                'is_active' =>
                    true,

                'is_open' =>
                    false,
            ],

            [
                'id' =>
                    self::CANTEEN_INACTIVE,

                'owner_user_id' =>
                    'cccccccc-3333-4333-8333-cccccccccccc',

                'name' =>
                    'Kantin Nonaktif',

                'type' =>
                    'canteen',

                'description' =>
                    null,

                'logo_url' =>
                    null,

                'is_active' =>
                    false,

                'is_open' =>
                    false,
            ],

            [
                'id' =>
                    self::COOPERATIVE_MAIN,

                'owner_user_id' =>
                    'dddddddd-4444-4444-8444-dddddddddddd',

                'name' =>
                    'Koperasi Sekolah',

                'type' =>
                    'cooperative',

                'description' =>
                    'Koperasi sekolah.',

                'logo_url' =>
                    null,

                'is_active' =>
                    true,

                'is_open' =>
                    true,
            ],
        ]);

        DB::table('categories')->insert([
            [
                'id' =>
                    self::CANTEEN_CATEGORY,

                'merchant_id' =>
                    self::CANTEEN_MAIN,

                'name' =>
                    'Makanan',

                'slug' =>
                    'makanan',
            ],

            [
                'id' =>
                    self::COOPERATIVE_CATEGORY,

                'merchant_id' =>
                    self::COOPERATIVE_MAIN,

                'name' =>
                    'Alat Tulis',

                'slug' =>
                    'alat-tulis',
            ],
        ]);

        $products = [];

        for ($index = 1; $index <= 10; $index++) {
            $products[] = [
                'id' =>
                    sprintf(
                        '10000000-0000-4000-8000-%012d',
                        $index
                    ),

                'merchant_id' =>
                    self::CANTEEN_MAIN,

                'category_id' =>
                    self::CANTEEN_CATEGORY,

                'name' =>
                    "Produk Kantin {$index}",

                'slug' =>
                    "produk-kantin-{$index}",

                'description' =>
                    null,

                'price' =>
                    10000 + $index,

                'stock' =>
                    10,

                'image_url' =>
                    null,

                'is_active' =>
                    true,

                'created_at' =>
                    now()->subMinutes(
                        20 - $index
                    ),

                'updated_at' =>
                    now(),
            ];
        }

        for ($index = 1; $index <= 9; $index++) {
            $products[] = [
                'id' =>
                    sprintf(
                        '20000000-0000-4000-8000-%012d',
                        $index
                    ),

                'merchant_id' =>
                    self::COOPERATIVE_MAIN,

                'category_id' =>
                    self::COOPERATIVE_CATEGORY,

                'name' =>
                    "Produk Koperasi {$index}",

                'slug' =>
                    "produk-koperasi-{$index}",

                'description' =>
                    null,

                'price' =>
                    5000 + $index,

                'stock' =>
                    20,

                'image_url' =>
                    null,

                'is_active' =>
                    true,

                'created_at' =>
                    now()->subMinutes(
                        20 - $index
                    ),

                'updated_at' =>
                    now(),
            ];
        }

        $products[] = [
            'id' =>
                '30000000-0000-4000-8000-000000000001',

            'merchant_id' =>
                self::CANTEEN_MAIN,

            'category_id' =>
                self::CANTEEN_CATEGORY,

            'name' =>
                'Produk Nonaktif',

            'slug' =>
                'produk-nonaktif',

            'description' =>
                null,

            'price' =>
                7000,

            'stock' =>
                5,

            'image_url' =>
                null,

            'is_active' =>
                false,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];

        $products[] = [
            'id' =>
                '30000000-0000-4000-8000-000000000002',

            'merchant_id' =>
                self::CANTEEN_INACTIVE,

            'category_id' =>
                null,

            'name' =>
                'Produk Merchant Nonaktif',

            'slug' =>
                'produk-merchant-nonaktif',

            'description' =>
                null,

            'price' =>
                8000,

            'stock' =>
                5,

            'image_url' =>
                null,

            'is_active' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];

        $products[] = [
            'id' =>
                '30000000-0000-4000-8000-000000000003',

            'merchant_id' =>
                self::CANTEEN_MAIN,

            'category_id' =>
                self::CANTEEN_CATEGORY,

            'name' =>
                'Produk Dihapus',

            'slug' =>
                'produk-dihapus',

            'description' =>
                null,

            'price' =>
                9000,

            'stock' =>
                5,

            'image_url' =>
                null,

            'is_active' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];

        DB::table('products')->insert(
            $products
        );

        DB::table('products')
            ->where(
                'id',
                '30000000-0000-4000-8000-000000000003'
            )
            ->update([
                'deleted_at' =>
                    now(),
            ]);
    }

    public function test_public_home_returns_maximum_eight_products_per_type(): void
    {
        $response =
            $this->getJson(
                '/api/v1/public/home'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonCount(
                8,
                'data.canteen.products'
            )
            ->assertJsonCount(
                8,
                'data.cooperative.products'
            );

        $canteenIds =
            collect(
                $response->json(
                    'data.canteen.products'
                )
            )->pluck('id');

        $cooperativeIds =
            collect(
                $response->json(
                    'data.cooperative.products'
                )
            )->pluck('id');

        $this->assertFalse(
            $canteenIds->contains(
                '30000000-0000-4000-8000-000000000001'
            )
        );

        $this->assertFalse(
            $canteenIds->contains(
                '30000000-0000-4000-8000-000000000002'
            )
        );

        $this->assertFalse(
            $canteenIds->contains(
                '30000000-0000-4000-8000-000000000003'
            )
        );

        $this->assertCount(
            8,
            $cooperativeIds
        );
    }

    public function test_public_catalog_returns_complete_canteen_browsing_data(): void
    {
        $response =
            $this->getJson(
                '/api/v1/public/catalog?type=canteen'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonCount(
                2,
                'data.merchants'
            )
            ->assertJsonCount(
                10,
                'data.products'
            )
            ->assertJsonCount(
                1,
                'data.categories'
            );

        $merchants =
            collect(
                $response->json(
                    'data.merchants'
                )
            );

        $mainMerchant =
            $merchants->firstWhere(
                'id',
                self::CANTEEN_MAIN
            );

        $emptyMerchant =
            $merchants->firstWhere(
                'id',
                self::CANTEEN_EMPTY
            );

        $this->assertNotNull(
            $mainMerchant
        );

        $this->assertNotNull(
            $emptyMerchant
        );

        $this->assertSame(
            10,
            $mainMerchant[
                'products_count'
            ]
        );

        $this->assertSame(
            0,
            $emptyMerchant[
                'products_count'
            ]
        );

        $productIds =
            collect(
                $response->json(
                    'data.products'
                )
            )->pluck('id');

        $this->assertFalse(
            $productIds->contains(
                '30000000-0000-4000-8000-000000000001'
            )
        );

        $this->assertFalse(
            $productIds->contains(
                '30000000-0000-4000-8000-000000000002'
            )
        );

        $this->assertFalse(
            $productIds->contains(
                '30000000-0000-4000-8000-000000000003'
            )
        );
    }

    public function test_public_catalog_returns_complete_cooperative_browsing_data(): void
    {
        $response =
            $this->getJson(
                '/api/v1/public/catalog?type=cooperative'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonCount(
                1,
                'data.merchants'
            )
            ->assertJsonCount(
                9,
                'data.products'
            )
            ->assertJsonCount(
                1,
                'data.categories'
            )
            ->assertJsonPath(
                'data.merchants.0.products_count',
                9
            );
    }

    public function test_public_catalog_requires_type(): void
    {
        $this
            ->getJson(
                '/api/v1/public/catalog'
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'error.code',
                'VALIDATION_ERROR'
            );
    }

    public function test_public_catalog_rejects_invalid_type(): void
    {
        $this
            ->getJson(
                '/api/v1/public/catalog?type=invalid'
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'error.code',
                'VALIDATION_ERROR'
            );
    }
}
