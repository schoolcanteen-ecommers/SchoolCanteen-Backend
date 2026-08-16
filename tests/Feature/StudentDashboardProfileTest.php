<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentDashboardProfileTest extends TestCase
{
    private const STUDENT_A =
        '11111111-1111-4111-8111-111111111111';

    private const STUDENT_B =
        '22222222-2222-4222-8222-222222222222';

    private const MERCHANT =
        '33333333-3333-4333-8333-333333333333';

    private const ADMIN =
        '44444444-4444-4444-8444-444444444444';

    private const WALLET_A =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const WALLET_B =
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

        $this->createSchema();
        $this->seedProfiles();
    }

    private function createSchema(): void
    {
        Schema::create(
            'profiles',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->string('name');
                $table->string('phone')->nullable();

                $table->string('avatar_url')
                    ->nullable();

                $table->string('role');

                $table->timestamps();
            }
        );

        Schema::create(
            'student_profiles',
            function (Blueprint $table) {
                $table->uuid('user_id')->primary();

                $table->string('nis');
                $table->string('class');

                $table->string('major')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'wallets',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('user_id');

                $table->unsignedBigInteger(
                    'balance'
                )->default(0);

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();
            }
        );

        Schema::create(
            'orders',
            function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('user_id');
                $table->uuid('merchant_id')
                    ->nullable();

                $table->string('order_code')
                    ->nullable();

                $table->string('status');

                $table->unsignedBigInteger(
                    'total_amount'
                )->default(0);

                $table->timestamps();
            }
        );
    }

    private function seedProfiles(): void
    {
        DB::table('profiles')->insert([
            [
                'id' => self::STUDENT_A,
                'name' => 'Student A',
                'phone' => '081111111111',
                'avatar_url' => null,
                'role' => 'student',
            ],
            [
                'id' => self::STUDENT_B,
                'name' => 'Student B',
                'phone' => null,
                'avatar_url' => null,
                'role' => 'student',
            ],
            [
                'id' => self::MERCHANT,
                'name' => 'Merchant User',
                'phone' => null,
                'avatar_url' => null,
                'role' => 'merchant',
            ],
            [
                'id' => self::ADMIN,
                'name' => 'Admin User',
                'phone' => null,
                'avatar_url' => null,
                'role' => 'admin',
            ],
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

    private function seedWallets(): void
    {
        DB::table('wallets')->insert([
            [
                'id' => self::WALLET_A,
                'user_id' => self::STUDENT_A,
                'balance' => 125000,
                'is_active' => true,
            ],
            [
                'id' => self::WALLET_B,
                'user_id' => self::STUDENT_B,
                'balance' => 999999,
                'is_active' => true,
            ],
        ]);
    }

    public function test_dashboard_counts_only_authenticated_students_orders(): void
    {
        $this->seedWallets();

        DB::table('orders')->insert([
            [
                'id' =>
                    '10000000-0000-4000-8000-000000000001',
                'user_id' => self::STUDENT_A,
                'status' => 'waiting',
            ],
            [
                'id' =>
                    '10000000-0000-4000-8000-000000000002',
                'user_id' => self::STUDENT_A,
                'status' => 'confirmed',
            ],
            [
                'id' =>
                    '10000000-0000-4000-8000-000000000003',
                'user_id' => self::STUDENT_A,
                'status' => 'preparing',
            ],
            [
                'id' =>
                    '10000000-0000-4000-8000-000000000004',
                'user_id' => self::STUDENT_A,
                'status' => 'ready',
            ],
            [
                'id' =>
                    '10000000-0000-4000-8000-000000000005',
                'user_id' => self::STUDENT_A,
                'status' => 'completed',
            ],
            [
                'id' =>
                    '10000000-0000-4000-8000-000000000006',
                'user_id' => self::STUDENT_A,
                'status' => 'cancelled',
            ],
            [
                'id' =>
                    '20000000-0000-4000-8000-000000000001',
                'user_id' => self::STUDENT_B,
                'status' => 'waiting',
            ],
            [
                'id' =>
                    '20000000-0000-4000-8000-000000000002',
                'user_id' => self::STUDENT_B,
                'status' => 'completed',
            ],
        ]);

        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/student/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.active_orders',
                4
            )
            ->assertJsonPath(
                'data.completed_orders',
                1
            );
    }

    public function test_dashboard_returns_authenticated_students_wallet(): void
    {
        $this->seedWallets();

        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/student/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.wallet.id',
                self::WALLET_A
            )
            ->assertJsonPath(
                'data.wallet.balance',
                125000
            )
            ->assertJsonPath(
                'data.wallet.is_active',
                true
            );
    }

    public function test_dashboard_returns_404_when_wallet_is_missing(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/student/dashboard'
            );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'WALLET_NOT_FOUND'
            );
    }

    public function test_profile_returns_authenticated_student_data(): void
    {
        DB::table('student_profiles')->insert([
            'user_id' => self::STUDENT_A,
            'nis' => '12345678',
            'class' => 'XI RPL 1',
            'major' =>
                'Rekayasa Perangkat Lunak',
        ]);

        DB::table('student_profiles')->insert([
            'user_id' => self::STUDENT_B,
            'nis' => '87654321',
            'class' => 'XI TKJ 1',
            'major' =>
                'Teknik Komputer Jaringan',
        ]);

        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_A
                )
            )
            ->getJson(
                '/api/v1/student/profile'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                self::STUDENT_A
            )
            ->assertJsonPath(
                'data.name',
                'Student A'
            )
            ->assertJsonPath(
                'data.phone',
                '081111111111'
            )
            ->assertJsonPath(
                'data.role',
                'student'
            )
            ->assertJsonPath(
                'data.student_profile.nis',
                '12345678'
            )
            ->assertJsonPath(
                'data.student_profile.class',
                'XI RPL 1'
            )
            ->assertJsonPath(
                'data.student_profile.major',
                'Rekayasa Perangkat Lunak'
            );
    }

    public function test_profile_allows_missing_student_profile(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::STUDENT_B
                )
            )
            ->getJson(
                '/api/v1/student/profile'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                self::STUDENT_B
            )
            ->assertJsonPath(
                'data.student_profile',
                null
            );
    }

    public function test_merchant_cannot_access_student_dashboard(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::MERCHANT
                )
            )
            ->getJson(
                '/api/v1/student/dashboard'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_admin_cannot_access_student_profile(): void
    {
        $response = $this
            ->withHeaders(
                $this->authenticateAs(
                    self::ADMIN
                )
            )
            ->getJson(
                '/api/v1/student/profile'
            );

        $response
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }
}
