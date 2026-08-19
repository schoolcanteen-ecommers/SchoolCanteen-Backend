<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentOrderModifierCheckoutTest extends TestCase
{
    private const STUDENT =
        '11111111-1111-4111-8111-111111111111';

    private const MERCHANT =
        '22222222-2222-4222-8222-222222222222';

    private const MERCHANT_USER =
        'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const PRODUCT =
        '33333333-3333-4333-8333-333333333333';

    private const OTHER_PRODUCT =
        '44444444-4444-4444-8444-444444444444';

    private const WALLET =
        '55555555-5555-4555-8555-555555555555';

    private const MERCHANT_WALLET =
        '66666666-6666-4666-8666-666666666666';

    private const REQUIRED_GROUP =
        '77777777-7777-4777-8777-777777777777';

    private const OPTION_NORMAL =
        '88888888-8888-4888-8888-888888888888';

    private const OPTION_EXTRA =
        '99999999-9999-4999-8999-999999999999';

    private const OTHER_GROUP =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const OTHER_OPTION =
        'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.supabase.url' =>
                'https://supabase.test',

            'services.supabase.anon_key' =>
                'test-anon-key',
        ]);

        Http::preventStrayRequests();

        /*
         * Satu fake Supabase untuk seluruh test.
         *
         * Jangan memasang ulang Http::fake()
         * setiap ganti role dalam test yang sama.
         * Identity ditentukan dari bearer token.
         */
        Http::fake(function ($request) {
            $authorization =
                $request->header(
                    'Authorization'
                )[0] ?? '';

            $userId =
                match ($authorization) {
                    'Bearer student-test-access-token' =>
                        self::STUDENT,

                    'Bearer merchant-test-access-token' =>
                        self::MERCHANT_USER,

                    default =>
                        null,
                };

            if (!$userId) {
                return Http::response([
                    'message' =>
                        'Unknown test access token.',
                ], 401);
            }

            return Http::response([
                'id' =>
                    $userId,
            ], 200);
        });

        $this->createSchema();
        $this->seedBaseData();
    }

    private function createSchema(): void
    {
        foreach ([
            'order_item_modifiers',
            'merchant_wallet_transactions',
            'wallet_transactions',
            'escrow_transactions',
            'pickups',
            'order_items',
            'orders',
            'product_modifier_options',
            'product_modifier_groups',
            'pickup_slots',
            'merchant_wallets',
            'wallets',
            'products',
            'merchants',
            'profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create(
            'profiles',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->string('name');

                $table->string('phone')
                    ->nullable();

                $table->text('avatar_url')
                    ->nullable();

                $table->string('role');

                $table->timestamps();
            }
        );

        Schema::create(
            'merchants',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'owner_user_id'
                )->nullable();

                $table->string('name');

                $table->string('type')
                    ->nullable();

                $table->text(
                    'description'
                )->nullable();

                $table->text(
                    'logo_url'
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->boolean(
                    'is_open'
                )->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'products',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'merchant_id'
                );

                $table->uuid(
                    'category_id'
                )->nullable();

                $table->string('name');

                $table->string('slug');

                $table->text(
                    'description'
                )->nullable();

                $table->unsignedBigInteger(
                    'price'
                );

                $table->integer(
                    'stock'
                );

                $table->text(
                    'image_url'
                )->nullable();

                $table->string(
                    'image_public_id'
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->softDeletes();
                $table->timestamps();
            }
        );

        Schema::create(
            'wallets',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'user_id'
                );

                $table->unsignedBigInteger(
                    'balance'
                )->default(0);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'merchant_wallets',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'merchant_id'
                );

                $table->unsignedBigInteger(
                    'pending_balance'
                )->default(0);

                $table->unsignedBigInteger(
                    'available_balance'
                )->default(0);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'pickup_slots',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'merchant_id'
                );

                $table->timestamp(
                    'start_at'
                );

                $table->timestamp(
                    'end_at'
                );

                $table->integer(
                    'capacity'
                )->default(10);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();
            }
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
                );

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

        Schema::create(
            'orders',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'user_id'
                );

                $table->uuid(
                    'merchant_id'
                );

                $table->uuid(
                    'pickup_slot_id'
                )->nullable();

                $table->string(
                    'order_code'
                );

                $table->string(
                    'status'
                );

                $table->unsignedBigInteger(
                    'total_amount'
                );

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamp(
                    'confirmed_at'
                )->nullable();

                $table->timestamp(
                    'preparing_at'
                )->nullable();

                $table->timestamp(
                    'ready_at'
                )->nullable();

                $table->timestamp(
                    'completed_at'
                )->nullable();

                $table->timestamp(
                    'cancelled_at'
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'order_items',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'order_id'
                );

                $table->uuid(
                    'product_id'
                )->nullable();

                $table->string(
                    'product_name'
                );

                $table->text(
                    'product_image_url'
                )->nullable();

                $table->unsignedBigInteger(
                    'unit_price'
                );

                $table->integer(
                    'quantity'
                );

                $table->unsignedBigInteger(
                    'subtotal'
                );

                $table->string(
                    'notes',
                    120
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'order_item_modifiers',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'order_item_id'
                );

                $table->uuid(
                    'modifier_group_id'
                )->nullable();

                $table->uuid(
                    'modifier_option_id'
                )->nullable();

                $table->string(
                    'group_name',
                    100
                );

                $table->string(
                    'option_name',
                    100
                );

                $table->unsignedBigInteger(
                    'price_delta'
                )->default(0);

                $table->timestamps();
            }
        );

        Schema::create(
            'wallet_transactions',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'wallet_id'
                );

                $table->string(
                    'type'
                );

                $table->string(
                    'direction'
                );

                $table->unsignedBigInteger(
                    'amount'
                );

                $table->string(
                    'status'
                );

                $table->string(
                    'reference_type'
                )->nullable();

                $table->uuid(
                    'reference_id'
                )->nullable();

                $table->text(
                    'description'
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'merchant_wallet_transactions',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'merchant_wallet_id'
                );

                $table->string(
                    'type'
                );

                $table->string(
                    'direction'
                );

                $table->unsignedBigInteger(
                    'amount'
                );

                $table->string(
                    'status'
                );

                $table->string(
                    'reference_type'
                )->nullable();

                $table->uuid(
                    'reference_id'
                )->nullable();

                $table->text(
                    'description'
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'escrow_transactions',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'order_id'
                );

                $table->unsignedBigInteger(
                    'amount'
                );

                $table->string(
                    'status'
                );

                $table->timestamp(
                    'held_at'
                )->nullable();

                $table->timestamp(
                    'released_at'
                )->nullable();

                $table->timestamp(
                    'refunded_at'
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'pickups',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid(
                    'order_id'
                );

                $table->string(
                    'pickup_token'
                );

                $table->string(
                    'pickup_code'
                );

                $table->string(
                    'status'
                );

                $table->uuid(
                    'verified_by'
                )->nullable();

                $table->timestamp(
                    'verified_at'
                )->nullable();

                $table->timestamps();
            }
        );
    }

    private function seedBaseData(): void
    {
        DB::table(
            'profiles'
        )->insert([
            'id' =>
                self::STUDENT,

            'name' =>
                'Student Test',

            'role' =>
                'student',
        ]);

        DB::table(
            'profiles'
        )->insert([
            'id' =>
                self::MERCHANT_USER,

            'name' =>
                'Merchant Test',

            'role' =>
                'merchant',
        ]);

        DB::table(
            'merchants'
        )->insert([
            'id' =>
                self::MERCHANT,

            'owner_user_id' =>
                self::MERCHANT_USER,

            'name' =>
                'Kantin Test',

            'type' =>
                'canteen',

            'is_active' =>
                true,

            'is_open' =>
                true,
        ]);

        DB::table(
            'products'
        )->insert([
            [
                'id' =>
                    self::PRODUCT,

                'merchant_id' =>
                    self::MERCHANT,

                'name' =>
                    'Mie Ayam',

                'slug' =>
                    'mie-ayam',

                'price' =>
                    10000,

                'stock' =>
                    10,

                'is_active' =>
                    true,
            ],

            [
                'id' =>
                    self::OTHER_PRODUCT,

                'merchant_id' =>
                    self::MERCHANT,

                'name' =>
                    'Nasi Goreng',

                'slug' =>
                    'nasi-goreng',

                'price' =>
                    15000,

                'stock' =>
                    10,

                'is_active' =>
                    true,
            ],
        ]);

        DB::table(
            'wallets'
        )->insert([
            'id' =>
                self::WALLET,

            'user_id' =>
                self::STUDENT,

            'balance' =>
                100000,

            'is_active' =>
                true,
        ]);

        DB::table(
            'merchant_wallets'
        )->insert([
            'id' =>
                self::MERCHANT_WALLET,

            'merchant_id' =>
                self::MERCHANT,

            'pending_balance' =>
                0,

            'available_balance' =>
                0,

            'is_active' =>
                true,
        ]);

        DB::table(
            'product_modifier_groups'
        )->insert([
            [
                'id' =>
                    self::REQUIRED_GROUP,

                'product_id' =>
                    self::PRODUCT,

                'name' =>
                    'Level Pedas',

                'selection_type' =>
                    'single',

                'is_required' =>
                    true,

                'min_select' =>
                    1,

                'max_select' =>
                    1,

                'sort_order' =>
                    0,

                'is_active' =>
                    true,
            ],

            [
                'id' =>
                    self::OTHER_GROUP,

                'product_id' =>
                    self::OTHER_PRODUCT,

                'name' =>
                    'Ukuran',

                'selection_type' =>
                    'single',

                'is_required' =>
                    false,

                'min_select' =>
                    0,

                'max_select' =>
                    1,

                'sort_order' =>
                    0,

                'is_active' =>
                    true,
            ],
        ]);

        DB::table(
            'product_modifier_options'
        )->insert([
            [
                'id' =>
                    self::OPTION_NORMAL,

                'modifier_group_id' =>
                    self::REQUIRED_GROUP,

                'name' =>
                    'Tidak Pedas',

                'price_delta' =>
                    0,

                'sort_order' =>
                    0,

                'is_active' =>
                    true,
            ],

            [
                'id' =>
                    self::OPTION_EXTRA,

                'modifier_group_id' =>
                    self::REQUIRED_GROUP,

                'name' =>
                    'Pedas + Telur',

                'price_delta' =>
                    2000,

                'sort_order' =>
                    1,

                'is_active' =>
                    true,
            ],

            [
                'id' =>
                    self::OTHER_OPTION,

                'modifier_group_id' =>
                    self::OTHER_GROUP,

                'name' =>
                    'Jumbo',

                'price_delta' =>
                    3000,

                'sort_order' =>
                    0,

                'is_active' =>
                    true,
            ],
        ]);
    }

    private function authHeaders(
        string $userId = self::STUDENT
    ): array {
        $token =
            match ($userId) {
                self::STUDENT =>
                    'student-test-access-token',

                self::MERCHANT_USER =>
                    'merchant-test-access-token',

                default =>
                    'unknown-test-access-token',
            };

        return [
            'Authorization' =>
                "Bearer {$token}",

            'Accept' =>
                'application/json',
        ];
    }

    private function checkout(
        array $items
    ) {
        return $this
            ->withHeaders(
                $this->authHeaders()
            )
            ->postJson(
                '/api/v1/student/orders',
                [
                    'merchant_id' =>
                        self::MERCHANT,

                    'pickup_slot_id' =>
                        null,

                    'items' =>
                        $items,

                    'notes' =>
                        'Order test',
                ]
            );
    }

    public function test_duplicate_product_lines_use_aggregate_stock(): void
    {
        DB::table(
            'products'
        )
            ->where(
                'id',
                self::PRODUCT
            )
            ->update([
                'stock' => 3,
            ]);

        $response =
            $this->checkout([
                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        2,

                    'modifier_option_ids' => [
                        self::OPTION_NORMAL,
                    ],
                ],

                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        2,

                    'modifier_option_ids' => [
                        self::OPTION_EXTRA,
                    ],
                ],
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'INSUFFICIENT_STOCK'
            )
            ->assertJsonPath(
                'error.details.requested_quantity',
                4
            )
            ->assertJsonPath(
                'error.details.available_quantity',
                3
            );

        $this->assertDatabaseHas(
            'products',
            [
                'id' =>
                    self::PRODUCT,

                'stock' =>
                    3,
            ]
        );

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    public function test_required_modifier_must_be_selected(): void
    {
        $response =
            $this->checkout([
                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        1,

                    'modifier_option_ids' =>
                        [],
                ],
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'MODIFIER_SELECTION_REQUIRED'
            )
            ->assertJsonPath(
                'error.details.modifier_group_id',
                self::REQUIRED_GROUP
            );

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    public function test_modifier_option_from_other_product_is_rejected(): void
    {
        $response =
            $this->checkout([
                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        1,

                    'modifier_option_ids' => [
                        self::OTHER_OPTION,
                    ],
                ],
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'MODIFIER_OPTION_INVALID'
            )
            ->assertJsonPath(
                'error.details.modifier_option_id',
                self::OTHER_OPTION
            );

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    public function test_checkout_calculates_server_price_and_snapshots_each_line(): void
    {
        $response =
            $this->checkout([
                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        1,

                    /*
                     * Harus diabaikan backend.
                     */
                    'unit_price' =>
                        1,

                    'modifier_option_ids' => [
                        self::OPTION_NORMAL,
                    ],

                    'notes' =>
                        'Tanpa daun bawang',
                ],

                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        2,

                    /*
                     * Harus diabaikan backend.
                     */
                    'unit_price' =>
                        1,

                    'modifier_option_ids' => [
                        self::OPTION_EXTRA,
                    ],

                    'notes' =>
                        'Pedas',
                ],
            ]);

        /*
         * Line 1:
         * 10.000 x 1 = 10.000
         *
         * Line 2:
         * (10.000 + 2.000) x 2
         * = 24.000
         *
         * Total = 34.000
         */
        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.total_amount',
                34000
            )
            ->assertJsonPath(
                'data.remaining_balance',
                66000
            )
            ->assertJsonPath(
                'data.items.0.unit_price',
                10000
            )
            ->assertJsonPath(
                'data.items.1.unit_price',
                12000
            )
            ->assertJsonPath(
                'data.items.1.subtotal',
                24000
            )
            ->assertJsonPath(
                'data.items.1.modifiers.0.option_name',
                'Pedas + Telur'
            );

        $this->assertDatabaseCount(
            'orders',
            1
        );

        $this->assertDatabaseCount(
            'order_items',
            2
        );

        $this->assertDatabaseHas(
            'order_items',
            [
                'product_id' =>
                    self::PRODUCT,

                'unit_price' =>
                    10000,

                'quantity' =>
                    1,

                'subtotal' =>
                    10000,

                'notes' =>
                    'Tanpa daun bawang',
            ]
        );

        $this->assertDatabaseHas(
            'order_items',
            [
                'product_id' =>
                    self::PRODUCT,

                'unit_price' =>
                    12000,

                'quantity' =>
                    2,

                'subtotal' =>
                    24000,

                'notes' =>
                    'Pedas',
            ]
        );

        $this->assertDatabaseCount(
            'order_item_modifiers',
            2
        );

        $this->assertDatabaseHas(
            'order_item_modifiers',
            [
                'modifier_group_id' =>
                    self::REQUIRED_GROUP,

                'modifier_option_id' =>
                    self::OPTION_NORMAL,

                'group_name' =>
                    'Level Pedas',

                'option_name' =>
                    'Tidak Pedas',

                'price_delta' =>
                    0,
            ]
        );

        $this->assertDatabaseHas(
            'order_item_modifiers',
            [
                'modifier_group_id' =>
                    self::REQUIRED_GROUP,

                'modifier_option_id' =>
                    self::OPTION_EXTRA,

                'group_name' =>
                    'Level Pedas',

                'option_name' =>
                    'Pedas + Telur',

                'price_delta' =>
                    2000,
            ]
        );

        /*
         * Aggregate quantity = 1 + 2.
         */
        $this->assertDatabaseHas(
            'products',
            [
                'id' =>
                    self::PRODUCT,

                'stock' =>
                    7,
            ]
        );

        $this->assertDatabaseHas(
            'wallets',
            [
                'id' =>
                    self::WALLET,

                'balance' =>
                    66000,
            ]
        );

        $this->assertDatabaseHas(
            'merchant_wallets',
            [
                'id' =>
                    self::MERCHANT_WALLET,

                'pending_balance' =>
                    34000,
            ]
        );

        $this->assertDatabaseHas(
            'wallet_transactions',
            [
                'wallet_id' =>
                    self::WALLET,

                'type' =>
                    'payment',

                'direction' =>
                    'debit',

                'amount' =>
                    34000,

                'status' =>
                    'completed',
            ]
        );

        $this->assertDatabaseHas(
            'merchant_wallet_transactions',
            [
                'merchant_wallet_id' =>
                    self::MERCHANT_WALLET,

                'type' =>
                    'order_pending',

                'direction' =>
                    'credit',

                'amount' =>
                    34000,

                'status' =>
                    'completed',
            ]
        );

        $this->assertDatabaseHas(
            'escrow_transactions',
            [
                'amount' =>
                    34000,

                'status' =>
                    'held',
            ]
        );

        $this->assertDatabaseCount(
            'pickups',
            1
        );
    }

    public function test_student_order_detail_returns_modifier_snapshot(): void
    {
        $checkout =
            $this->checkout([
                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        1,

                    'modifier_option_ids' => [
                        self::OPTION_EXTRA,
                    ],

                    'notes' =>
                        'Tanpa daun bawang',
                ],
            ]);

        $checkout
            ->assertCreated();

        $orderId =
            $checkout->json(
                'data.order_id'
            );

        /*
         * Simulasikan merchant mengubah modifier
         * SETELAH order berhasil dibuat.
         */
        DB::table(
            'product_modifier_groups'
        )
            ->where(
                'id',
                self::REQUIRED_GROUP
            )
            ->update([
                'name' =>
                    'Level Baru',
            ]);

        DB::table(
            'product_modifier_options'
        )
            ->where(
                'id',
                self::OPTION_EXTRA
            )
            ->update([
                'name' =>
                    'Pilihan Baru',

                'price_delta' =>
                    9000,
            ]);

        $response =
            $this
                ->withHeaders(
                    $this->authHeaders(
                        self::STUDENT
                    )
                )
                ->getJson(
                    "/api/v1/student/orders/{$orderId}"
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.items.0.notes',
                'Tanpa daun bawang'
            )
            ->assertJsonPath(
                'data.items.0.unit_price',
                12000
            )
            ->assertJsonPath(
                'data.items.0.modifiers.0.group_name',
                'Level Pedas'
            )
            ->assertJsonPath(
                'data.items.0.modifiers.0.option_name',
                'Pedas + Telur'
            )
            ->assertJsonPath(
                'data.items.0.modifiers.0.price_delta',
                2000
            );
    }

    public function test_merchant_order_list_and_detail_return_modifier_snapshot(): void
    {
        $checkout =
            $this->checkout([
                [
                    'product_id' =>
                        self::PRODUCT,

                    'quantity' =>
                        1,

                    'modifier_option_ids' => [
                        self::OPTION_EXTRA,
                    ],

                    'notes' =>
                        'Pedas terpisah',
                ],
            ]);

        $checkout
            ->assertCreated();

        $orderId =
            $checkout->json(
                'data.order_id'
            );

        /*
         * Live modifier berubah setelah transaksi.
         * History merchant tetap harus membaca
         * snapshot order_item_modifiers.
         */
        DB::table(
            'product_modifier_groups'
        )
            ->where(
                'id',
                self::REQUIRED_GROUP
            )
            ->update([
                'name' =>
                    'Level Pedas Baru',
            ]);

        DB::table(
            'product_modifier_options'
        )
            ->where(
                'id',
                self::OPTION_EXTRA
            )
            ->update([
                'name' =>
                    'Super Pedas Baru',

                'price_delta' =>
                    7500,
            ]);

        $listResponse =
            $this
                ->withHeaders(
                    $this->authHeaders(
                        self::MERCHANT_USER
                    )
                )
                ->getJson(
                    '/api/v1/merchant/orders'
                );

        $listResponse
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $orderId
            )
            ->assertJsonPath(
                'data.0.items.0.notes',
                'Pedas terpisah'
            )
            ->assertJsonPath(
                'data.0.items.0.modifiers.0.group_name',
                'Level Pedas'
            )
            ->assertJsonPath(
                'data.0.items.0.modifiers.0.option_name',
                'Pedas + Telur'
            )
            ->assertJsonPath(
                'data.0.items.0.modifiers.0.price_delta',
                2000
            );

        $detailResponse =
            $this
                ->withHeaders(
                    $this->authHeaders(
                        self::MERCHANT_USER
                    )
                )
                ->getJson(
                    "/api/v1/merchant/orders/{$orderId}"
                );

        $detailResponse
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $orderId
            )
            ->assertJsonPath(
                'data.items.0.notes',
                'Pedas terpisah'
            )
            ->assertJsonPath(
                'data.items.0.unit_price',
                12000
            )
            ->assertJsonPath(
                'data.items.0.modifiers.0.group_name',
                'Level Pedas'
            )
            ->assertJsonPath(
                'data.items.0.modifiers.0.option_name',
                'Pedas + Telur'
            )
            ->assertJsonPath(
                'data.items.0.modifiers.0.price_delta',
                2000
            );
    }

}
