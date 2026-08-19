<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductResolveTest extends TestCase
{
    private const MERCHANT_OPEN =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const MERCHANT_CLOSED =
        'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const MERCHANT_INACTIVE =
        'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const PRODUCT_FIRST =
        '11111111-1111-4111-8111-111111111111';

    private const PRODUCT_SECOND =
        '22222222-2222-4222-8222-222222222222';

    private const PRODUCT_INACTIVE =
        '33333333-3333-4333-8333-333333333333';

    private const PRODUCT_CLOSED_MERCHANT =
        '44444444-4444-4444-8444-444444444444';

    private const PRODUCT_INACTIVE_MERCHANT =
        '55555555-5555-4555-8555-555555555555';

    private const PRODUCT_DELETED =
        '66666666-6666-4666-8666-666666666666';

    private const PRODUCT_MISSING =
        '77777777-7777-4777-8777-777777777777';

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

                $table->unsignedBigInteger('price');

                $table->integer('stock');

                $table->string('image_url')
                    ->nullable();

                $table->string('image_public_id')
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
                'id' => self::MERCHANT_OPEN,
                'owner_user_id' =>
                    'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa',
                'name' => 'Kantin Aktif',
                'type' => 'canteen',
                'is_active' => true,
                'is_open' => true,
            ],
            [
                'id' => self::MERCHANT_CLOSED,
                'owner_user_id' =>
                    'bbbbbbbb-2222-4222-8222-bbbbbbbbbbbb',
                'name' => 'Kantin Tutup',
                'type' => 'canteen',
                'is_active' => true,
                'is_open' => false,
            ],
            [
                'id' => self::MERCHANT_INACTIVE,
                'owner_user_id' =>
                    'cccccccc-3333-4333-8333-cccccccccccc',
                'name' => 'Kantin Nonaktif',
                'type' => 'canteen',
                'is_active' => false,
                'is_open' => false,
            ],
        ]);

        DB::table('products')->insert([
            [
                'id' => self::PRODUCT_FIRST,
                'merchant_id' =>
                    self::MERCHANT_OPEN,
                'name' => 'Produk Pertama',
                'slug' => 'produk-pertama',
                'price' => 10000,
                'stock' => 10,
                'is_active' => true,
            ],
            [
                'id' => self::PRODUCT_SECOND,
                'merchant_id' =>
                    self::MERCHANT_OPEN,
                'name' => 'Produk Kedua',
                'slug' => 'produk-kedua',
                'price' => 15000,
                'stock' => 8,
                'is_active' => true,
            ],
            [
                'id' => self::PRODUCT_INACTIVE,
                'merchant_id' =>
                    self::MERCHANT_OPEN,
                'name' => 'Produk Nonaktif',
                'slug' => 'produk-nonaktif',
                'price' => 12000,
                'stock' => 5,
                'is_active' => false,
            ],
            [
                'id' =>
                    self::PRODUCT_CLOSED_MERCHANT,
                'merchant_id' =>
                    self::MERCHANT_CLOSED,
                'name' => 'Produk Merchant Tutup',
                'slug' =>
                    'produk-merchant-tutup',
                'price' => 9000,
                'stock' => 7,
                'is_active' => true,
            ],
            [
                'id' =>
                    self::PRODUCT_INACTIVE_MERCHANT,
                'merchant_id' =>
                    self::MERCHANT_INACTIVE,
                'name' =>
                    'Produk Merchant Nonaktif',
                'slug' =>
                    'produk-merchant-nonaktif',
                'price' => 8000,
                'stock' => 6,
                'is_active' => true,
            ],
            [
                'id' => self::PRODUCT_DELETED,
                'merchant_id' =>
                    self::MERCHANT_OPEN,
                'name' => 'Produk Dihapus',
                'slug' => 'produk-dihapus',
                'price' => 5000,
                'stock' => 3,
                'is_active' => true,
            ],
        ]);

        DB::table('products')
            ->where(
                'id',
                self::PRODUCT_DELETED
            )
            ->update([
                'deleted_at' => now(),
            ]);
    }

    public function test_public_resolver_returns_products_in_requested_order(): void
    {
        $response = $this->postJson(
            '/api/v1/products/resolve',
            [
                'product_ids' => [
                    self::PRODUCT_SECOND,
                    self::PRODUCT_FIRST,
                    self::PRODUCT_CLOSED_MERCHANT,
                ],
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.products.0.id',
                self::PRODUCT_SECOND
            )
            ->assertJsonPath(
                'data.products.1.id',
                self::PRODUCT_FIRST
            )
            ->assertJsonPath(
                'data.products.2.id',
                self::PRODUCT_CLOSED_MERCHANT
            )
            ->assertJsonPath(
                'data.products.2.merchant.is_open',
                false
            )
            ->assertJsonPath(
                'data.unavailable_product_ids',
                []
            );
    }

    public function test_unavailable_products_are_reported_without_failing_batch(): void
    {
        $invalidLocalStorageId =
            'broken-product-id';

        $response = $this->postJson(
            '/api/v1/products/resolve',
            [
                'product_ids' => [
                    self::PRODUCT_FIRST,
                    self::PRODUCT_INACTIVE,
                    self::PRODUCT_INACTIVE_MERCHANT,
                    self::PRODUCT_DELETED,
                    self::PRODUCT_MISSING,
                    $invalidLocalStorageId,
                ],
            ]
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.products'
            )
            ->assertJsonPath(
                'data.products.0.id',
                self::PRODUCT_FIRST
            )
            ->assertJsonPath(
                'data.unavailable_product_ids',
                [
                    self::PRODUCT_INACTIVE,
                    self::PRODUCT_INACTIVE_MERCHANT,
                    self::PRODUCT_DELETED,
                    self::PRODUCT_MISSING,
                    $invalidLocalStorageId,
                ]
            );
    }

    public function test_duplicate_product_ids_are_rejected(): void
    {
        $response = $this->postJson(
            '/api/v1/products/resolve',
            [
                'product_ids' => [
                    self::PRODUCT_FIRST,
                    self::PRODUCT_FIRST,
                ],
            ]
        );

        $response->assertStatus(422);
    }

    public function test_empty_product_ids_are_rejected(): void
    {
        $response = $this->postJson(
            '/api/v1/products/resolve',
            [
                'product_ids' => [],
            ]
        );

        $response->assertStatus(422);
    }
}
