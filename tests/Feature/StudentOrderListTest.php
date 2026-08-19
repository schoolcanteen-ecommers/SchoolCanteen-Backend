<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentOrderListTest extends TestCase
{
    private const STUDENT_A =
        '11111111-1111-4111-8111-111111111111';

    private const STUDENT_B =
        '22222222-2222-4222-8222-222222222222';

    private const MERCHANT =
        '33333333-3333-4333-8333-333333333333';

    private const ORDER_A =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const ORDER_B =
        'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const PICKUP_SLOT =
        'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

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

        $this->createSchema();
        $this->seedData();
    }

    private function createSchema(): void
    {
        Schema::dropIfExists(
            'escrow_transactions'
        );

        Schema::dropIfExists(
            'order_item_modifiers'
        );

        Schema::dropIfExists(
            'order_items'
        );

        Schema::dropIfExists(
            'pickups'
        );

        Schema::dropIfExists(
            'orders'
        );

        Schema::dropIfExists(
            'pickup_slots'
        );

        Schema::dropIfExists(
            'merchants'
        );

        Schema::dropIfExists(
            'profiles'
        );

        Schema::create(
            'profiles',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->string('name');

                $table->string('phone')
                    ->nullable();

                $table->string('avatar_url')
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

                $table->uuid('owner_user_id')
                    ->nullable();

                $table->string('name');

                $table->string('type')
                    ->nullable();

                $table->text('description')
                    ->nullable();

                $table->string('logo_url')
                    ->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->boolean('is_open')
                    ->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'pickup_slots',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid('merchant_id');

                $table->timestamp('start_at');
                $table->timestamp('end_at');

                $table->integer('capacity')
                    ->default(10);

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'orders',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid('user_id');
                $table->uuid('merchant_id');

                $table->uuid('pickup_slot_id')
                    ->nullable();

                $table->string('order_code');
                $table->string('status');

                $table->unsignedBigInteger(
                    'total_amount'
                );

                $table->text('notes')
                    ->nullable();

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
            'pickups',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid('order_id');

                $table->string(
                    'pickup_token'
                );

                $table->string(
                    'pickup_code'
                );

                $table->string('status');

                $table->uuid('verified_by')
                    ->nullable();

                $table->timestamp(
                    'verified_at'
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'order_items',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid('order_id');

                $table->uuid('product_id')
                    ->nullable();

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
            'escrow_transactions',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->uuid('order_id');

                $table->unsignedBigInteger(
                    'amount'
                );

                $table->string('status');

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
    }

    private function seedData(): void
    {
        DB::table('profiles')->insert([
            [
                'id' => self::STUDENT_A,
                'name' => 'Student A',
                'role' => 'student',
            ],
            [
                'id' => self::STUDENT_B,
                'name' => 'Student B',
                'role' => 'student',
            ],
        ]);

        DB::table('merchants')->insert([
            'id' => self::MERCHANT,
            'name' => 'Kantin Test',
            'type' => 'canteen',
            'logo_url' => null,
            'is_active' => true,
            'is_open' => true,
        ]);

        DB::table('pickup_slots')->insert([
            'id' => self::PICKUP_SLOT,
            'merchant_id' =>
                self::MERCHANT,

            'start_at' =>
                '2026-08-16 10:00:00',

            'end_at' =>
                '2026-08-16 10:30:00',

            'capacity' => 10,
            'is_active' => true,
        ]);

        DB::table('orders')->insert([
            [
                'id' => self::ORDER_A,

                'user_id' =>
                    self::STUDENT_A,

                'merchant_id' =>
                    self::MERCHANT,

                'pickup_slot_id' =>
                    self::PICKUP_SLOT,

                'order_code' =>
                    'SC-ORDER-A',

                'status' => 'waiting',

                'total_amount' =>
                    15000,
            ],
            [
                'id' => self::ORDER_B,

                'user_id' =>
                    self::STUDENT_B,

                'merchant_id' =>
                    self::MERCHANT,

                'pickup_slot_id' =>
                    self::PICKUP_SLOT,

                'order_code' =>
                    'SC-ORDER-B',

                'status' => 'completed',

                'total_amount' =>
                    25000,
            ],
        ]);

        DB::table('pickups')->insert([
            [
                'id' =>
                    'dddddddd-dddd-4ddd-8ddd-dddddddddddd',

                'order_id' =>
                    self::ORDER_A,

                'pickup_token' =>
                    'token-order-a',

                'pickup_code' =>
                    '123456',

                'status' =>
                    'waiting',
            ],
        ]);

        DB::table('order_items')->insert([
            [
                'id' =>
                    'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',

                'order_id' =>
                    self::ORDER_A,

                'product_id' =>
                    null,

                'product_name' =>
                    'Nasi Goreng',

                'product_image_url' =>
                    'https://example.test/nasi-goreng.jpg',

                'unit_price' =>
                    15000,

                'quantity' =>
                    1,

                'subtotal' =>
                    15000,
            ],
            [
                'id' =>
                    'ffffffff-ffff-4fff-8fff-ffffffffffff',

                'order_id' =>
                    self::ORDER_B,

                'product_id' =>
                    null,

                'product_name' =>
                    'Produk Student B',

                'product_image_url' => null,

                'unit_price' =>
                    25000,

                'quantity' =>
                    1,

                'subtotal' =>
                    25000,
            ],
        ]);

        DB::table(
            'escrow_transactions'
        )->insert([
            'id' =>
                '99999999-9999-4999-8999-999999999999',

            'order_id' =>
                self::ORDER_A,

            'amount' =>
                15000,

            'status' =>
                'held',

            'held_at' =>
                '2026-08-16 09:30:00',
        ]);
    }

    private function authHeaders(): array
    {
        Http::fake([
            'https://supabase.test/auth/v1/user' =>
                Http::response([
                    'id' =>
                        self::STUDENT_A,
                ], 200),
        ]);

        return [
            'Authorization' =>
                'Bearer test-access-token',

            'Accept' =>
                'application/json',
        ];
    }

    public function test_student_order_list_contains_data_needed_by_list_without_detail_requests(): void
    {
        $response = $this
            ->withHeaders(
                $this->authHeaders()
            )
            ->getJson(
                '/api/v1/student/orders'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                self::ORDER_A
            )
            ->assertJsonPath(
                'data.0.order_code',
                'SC-ORDER-A'
            )
            ->assertJsonPath(
                'data.0.merchant.name',
                'Kantin Test'
            )
            ->assertJsonPath(
                'data.0.pickup_slot.id',
                self::PICKUP_SLOT
            )
            ->assertJsonPath(
                'data.0.items.0.product_name',
                'Nasi Goreng'
            )
            ->assertJsonPath(
                'data.0.items.0.product_image_url',
                'https://example.test/nasi-goreng.jpg'
            )
            ->assertJsonPath(
                'data.0.items.0.quantity',
                1
            )
            ->assertJsonPath(
                'data.0.pickup.code',
                '123456'
            )
            ->assertJsonPath(
                'data.0.escrow.status',
                'held'
            );
    }
}
