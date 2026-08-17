<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\MerchantWallet;
use App\Models\PickupSlot;
use App\Models\Product;
use App\Models\Profile;
use App\Models\StudentProfile;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolCanteenSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Demo User IDs dari Supabase Auth
            |--------------------------------------------------------------------------
            */

            $studentUserId = env('SEED_STUDENT_USER_ID');
            $canteenUserId = env('SEED_CANTEEN_USER_ID');
            $cooperativeUserId = env('SEED_COOPERATIVE_USER_ID');
            $adminUserId = env('SEED_ADMIN_USER_ID');

            if (
                !$studentUserId ||
                !$canteenUserId ||
                !$cooperativeUserId ||
                !$adminUserId
            ) {
                throw new \RuntimeException(
                    'Seed user IDs belum lengkap di file .env.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Profiles
            |--------------------------------------------------------------------------
            */

            Profile::updateOrCreate(
                ['id' => $studentUserId],
                [
                    'name' => 'Andi Pratama',
                    'phone' => '081234567890',
                    'role' => 'student',
                ]
            );

            Profile::updateOrCreate(
                ['id' => $canteenUserId],
                [
                    'name' => 'Bu Ani',
                    'phone' => '081234567891',
                    'role' => 'merchant',
                ]
            );

            Profile::updateOrCreate(
                ['id' => $cooperativeUserId],
                [
                    'name' => 'Petugas Koperasi',
                    'phone' => '081234567892',
                    'role' => 'merchant',
                ]
            );

            Profile::updateOrCreate(
                ['id' => $adminUserId],
                [
                    'name' => 'Administrator',
                    'phone' => '081234567893',
                    'role' => 'admin',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Student Profile
            |--------------------------------------------------------------------------
            */

            StudentProfile::updateOrCreate(
                ['user_id' => $studentUserId],
                [
                    'nis' => '20260001',
                    'class' => 'XI RPL 1',
                    'major' => 'RPL',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Student Wallet
            |--------------------------------------------------------------------------
            */

            $studentWallet = Wallet::firstOrCreate(
                ['user_id' => $studentUserId],
                [
                    'id' => Str::uuid()->toString(),
                    'balance' => 100000,
                    'is_active' => true,
                ]
            );

            if ($studentWallet->transactions()->count() === 0) {
                WalletTransaction::create([
                    'id' => Str::uuid()->toString(),
                    'wallet_id' => $studentWallet->id,
                    'type' => 'adjustment',
                    'direction' => 'credit',
                    'amount' => 100000,
                    'status' => 'completed',
                    'description' => 'Saldo awal akun demo',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Merchant Kantin
            |--------------------------------------------------------------------------
            */

            $canteen = Merchant::firstOrCreate(
                ['owner_user_id' => $canteenUserId],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'Kantin Bu Ani',
                    'type' => 'canteen',
                    'description' => 'Kantin makanan dan minuman sekolah.',
                    'is_active' => true,
                    'is_open' => true,
                ]
            );

            MerchantWallet::firstOrCreate(
                ['merchant_id' => $canteen->id],
                [
                    'id' => Str::uuid()->toString(),
                    'pending_balance' => 0,
                    'available_balance' => 0,
                    'is_active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Merchant Koperasi
            |--------------------------------------------------------------------------
            */

            $cooperative = Merchant::firstOrCreate(
                ['owner_user_id' => $cooperativeUserId],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'Koperasi Sekolah',
                    'type' => 'cooperative',
                    'description' => 'Kebutuhan alat tulis dan perlengkapan sekolah.',
                    'is_active' => true,
                    'is_open' => true,
                ]
            );

            MerchantWallet::firstOrCreate(
                ['merchant_id' => $cooperative->id],
                [
                    'id' => Str::uuid()->toString(),
                    'pending_balance' => 0,
                    'available_balance' => 0,
                    'is_active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Categories Kantin
            |--------------------------------------------------------------------------
            */

            $foodCategory = Category::firstOrCreate(
                [
                    'merchant_id' => $canteen->id,
                    'slug' => 'makanan',
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'Makanan',
                ]
            );

            $drinkCategory = Category::firstOrCreate(
                [
                    'merchant_id' => $canteen->id,
                    'slug' => 'minuman',
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'Minuman',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Categories Koperasi
            |--------------------------------------------------------------------------
            */

            $stationeryCategory = Category::firstOrCreate(
                [
                    'merchant_id' => $cooperative->id,
                    'slug' => 'alat-tulis',
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'Alat Tulis',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Products Kantin
            |--------------------------------------------------------------------------
            */

            $this->createProduct(
                $canteen->id,
                $foodCategory->id,
                'Nasi Ayam',
                'nasi-ayam',
                15000,
                50
            );

            $this->createProduct(
                $canteen->id,
                $foodCategory->id,
                'Mie Goreng',
                'mie-goreng',
                12000,
                40
            );

            $this->createProduct(
                $canteen->id,
                $drinkCategory->id,
                'Es Teh',
                'es-teh',
                4000,
                100
            );

            $this->createProduct(
                $canteen->id,
                $foodCategory->id,
                'Roti Bakar',
                'roti-bakar',
                8000,
                30
            );

            /*
            |--------------------------------------------------------------------------
            | Products Koperasi
            |--------------------------------------------------------------------------
            */

            $this->createProduct(
                $cooperative->id,
                $stationeryCategory->id,
                'Buku Tulis',
                'buku-tulis',
                6000,
                100
            );

            $this->createProduct(
                $cooperative->id,
                $stationeryCategory->id,
                'Pulpen',
                'pulpen',
                3000,
                100
            );

            $this->createProduct(
                $cooperative->id,
                $stationeryCategory->id,
                'Pensil',
                'pensil',
                2500,
                100
            );

            $this->createProduct(
                $cooperative->id,
                $stationeryCategory->id,
                'Penggaris',
                'penggaris',
                4000,
                50
            );

            /*
            |--------------------------------------------------------------------------
            | Pickup Slots Kantin
            |--------------------------------------------------------------------------
            */

            $today = Carbon::today();

            $slots = [
                ['09:40', '09:45'],
                ['09:45', '09:50'],
                ['09:50', '09:55'],
                ['09:55', '10:00'],
            ];

            foreach ($slots as [$start, $end]) {

                $startAt = Carbon::parse(
                    $today->format('Y-m-d') . ' ' . $start
                );

                $endAt = Carbon::parse(
                    $today->format('Y-m-d') . ' ' . $end
                );

                PickupSlot::firstOrCreate(
                    [
                        'merchant_id' => $canteen->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'capacity' => 10,
                        'is_active' => true,
                    ]
                );
            }
        });
    }

    private function createProduct(
        string $merchantId,
        string $categoryId,
        string $name,
        string $slug,
        int $price,
        int $stock
    ): void {
        Product::firstOrCreate(
            [
                'merchant_id' => $merchantId,
                'slug' => $slug,
            ],
            [
                'id' => Str::uuid()->toString(),
                'category_id' => $categoryId,
                'name' => $name,
                'description' => 'Produk demo SchoolCanteen.',
                'price' => $price,
                'stock' => $stock,
                'image_url' => null,
                'is_active' => true,
            ]
        );
    }
}