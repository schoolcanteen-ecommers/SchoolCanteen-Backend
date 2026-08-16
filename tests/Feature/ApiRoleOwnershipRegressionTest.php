<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiRoleOwnershipRegressionTest extends TestCase
{
    private const STUDENT_A =
        '11111111-1111-4111-8111-111111111111';

    private const STUDENT_B =
        '22222222-2222-4222-8222-222222222222';

    private const MERCHANT_USER_A =
        '33333333-3333-4333-8333-333333333333';

    private const MERCHANT_USER_B =
        '44444444-4444-4444-8444-444444444444';

    private const ADMIN =
        '55555555-5555-4555-8555-555555555555';

    private const MERCHANT_A =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const MERCHANT_B =
        'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const ORDER_B =
        'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const PICKUP_B =
        'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

    private const PRODUCT_B =
        'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';

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

        $this->createTestSchema();
        $this->seedTestData();
    }

    private function createTestSchema(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('pickups');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('merchants');
        Schema::dropIfExists('profiles');

        Schema::create(
            'profiles',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('avatar_url')->nullable();

                $table->string('role');

                $table->timestamps();
            }
        );

        Schema::create(
            'merchants',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('owner_user_id');

                $table->string('name');
                $table->string('type')->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->boolean('is_open')
                    ->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'orders',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

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
                $table->uuid('id')->primary();

                $table->uuid('order_id');

                $table->string('pickup_token');
                $table->string('pickup_code');

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

                $table->integer('stock');

                $table->string('image_url')
                    ->nullable();

                $table->string(
                    'image_public_id'
                )->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->softDeletes();
                $table->timestamps();
            }
        );
    }

    private function seedTestData(): void
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
            [
                'id' => self::MERCHANT_USER_A,
                'name' => 'Merchant User A',
                'role' => 'merchant',
            ],
            [
                'id' => self::MERCHANT_USER_B,
                'name' => 'Merchant User B',
                'role' => 'merchant',
            ],
            [
                'id' => self::ADMIN,
                'name' => 'Admin',
                'role' => 'admin',
            ],
        ]);

        DB::table('merchants')->insert([
            [
                'id' => self::MERCHANT_A,
                'owner_user_id' =>
                    self::MERCHANT_USER_A,
                'name' => 'Merchant A',
                'type' => 'canteen',
                'is_active' => true,
                'is_open' => true,
            ],
            [
                'id' => self::MERCHANT_B,
                'owner_user_id' =>
                    self::MERCHANT_USER_B,
                'name' => 'Merchant B',
                'type' => 'canteen',
                'is_active' => true,
                'is_open' => true,
            ],
        ]);

        DB::table('orders')->insert([
            'id' => self::ORDER_B,
            'user_id' => self::STUDENT_B,
            'merchant_id' => self::MERCHANT_B,
            'order_code' => 'SC-TEST-B',
            'status' => 'waiting',
            'total_amount' => 10000,
        ]);

        DB::table('pickups')->insert([
            'id' => self::PICKUP_B,
            'order_id' => self::ORDER_B,
            'pickup_token' =>
                'pickup-token-merchant-b',
            'pickup_code' => '654321',
            'status' => 'waiting',
        ]);

        DB::table('products')->insert([
            'id' => self::PRODUCT_B,
            'merchant_id' => self::MERCHANT_B,
            'name' => 'Produk Merchant B',
            'slug' => 'produk-merchant-b',
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    private function authenticateAs(
        string $profileId
    ): array {
        Http::fake([
            'https://supabase.test/auth/v1/user' =>
                Http::response([
                    'id' => $profileId,
                ], 200),
        ]);

        return [
            'Authorization' =>
                'Bearer test-access-token',

            'Accept' =>
                'application/json',
        ];
    }

    public function test_student_cannot_access_merchant_route(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/merchant/orders'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_student_cannot_access_admin_route(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/admin/dashboard'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_merchant_cannot_access_student_route(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT_USER_A
                )
            )
            ->getJson(
                '/api/v1/student/wallet'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_merchant_cannot_access_admin_route(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT_USER_A
                )
            )
            ->getJson(
                '/api/v1/admin/dashboard'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_admin_cannot_access_student_route(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::ADMIN
                )
            )
            ->getJson(
                '/api/v1/student/wallet'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_admin_cannot_access_merchant_route(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::ADMIN
                )
            )
            ->getJson(
                '/api/v1/merchant/orders'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_student_cannot_read_another_students_order(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/student/orders/'
                .self::ORDER_B
            );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'ORDER_NOT_FOUND'
            );
    }

    public function test_merchant_cannot_read_another_merchants_order(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT_USER_A
                )
            )
            ->getJson(
                '/api/v1/merchant/orders/'
                .self::ORDER_B
            );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'ORDER_NOT_FOUND'
            );
    }

    public function test_merchant_cannot_update_another_merchants_order(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT_USER_A
                )
            )
            ->patchJson(
                '/api/v1/merchant/orders/'
                .self::ORDER_B
                .'/status',
                [
                    'status' =>
                        'confirmed',
                ]
            );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'ORDER_NOT_FOUND'
            );
    }

    public function test_merchant_cannot_delete_another_merchants_product(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT_USER_A
                )
            )
            ->deleteJson(
                '/api/v1/merchant/products/'
                .self::PRODUCT_B
            );

        $response->assertStatus(404);
    }

    public function test_merchant_cannot_verify_another_merchants_pickup(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT_USER_A
                )
            )
            ->postJson(
                '/api/v1/merchant/pickups/verify',
                [
                    'pickup_code' =>
                        '654321',
                ]
            );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'PICKUP_NOT_FOUND'
            );
    }

    public function test_student_order_post_route_is_registered_once(): void
    {
        $routes = collect(
            Route::getRoutes()->getRoutes()
        )->filter(
            function ($route) {
                return
                    $route->uri() ===
                        'api/v1/student/orders'
                    && in_array(
                        'POST',
                        $route->methods(),
                        true
                    );
            }
        );

        $this->assertCount(
            1,
            $routes
        );
    }
}
