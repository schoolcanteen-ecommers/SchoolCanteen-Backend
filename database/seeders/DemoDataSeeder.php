<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $studentId = env('SEED_STUDENT_USER_ID');
        $canteenUserId = env('SEED_CANTEEN_USER_ID');
        $cooperativeUserId = env('SEED_COOPERATIVE_USER_ID');
        $adminId = env('SEED_ADMIN_USER_ID');

        if (
            !$studentId ||
            !$canteenUserId ||
            !$cooperativeUserId ||
            !$adminId
        ) {
            throw new \RuntimeException(
                'UUID akun demo di .env belum lengkap.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UUID data demo
        |--------------------------------------------------------------------------
        */

        $canteenMerchantId = '10000000-0000-4000-8000-000000000001';
        $cooperativeMerchantId = '10000000-0000-4000-8000-000000000002';

        $foodCategoryId = '20000000-0000-4000-8000-000000000001';
        $drinkCategoryId = '20000000-0000-4000-8000-000000000002';
        $stationeryCategoryId = '20000000-0000-4000-8000-000000000003';
        $bookCategoryId = '20000000-0000-4000-8000-000000000004';

        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Profiles
        |--------------------------------------------------------------------------
        */

        DB::table('profiles')->updateOrInsert(
            ['id' => $studentId],
            [
                'name' => 'Andi Pratama',
                'phone' => '081234567890',
                'avatar_url' => null,
                'role' => 'student',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('profiles')->updateOrInsert(
            ['id' => $canteenUserId],
            [
                'name' => 'Bu Ani',
                'phone' => '081234567891',
                'avatar_url' => null,
                'role' => 'merchant',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('profiles')->updateOrInsert(
            ['id' => $cooperativeUserId],
            [
                'name' => 'Petugas Koperasi',
                'phone' => '081234567892',
                'avatar_url' => null,
                'role' => 'merchant',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('profiles')->updateOrInsert(
            ['id' => $adminId],
            [
                'name' => 'Administrator',
                'phone' => '081234567893',
                'avatar_url' => null,
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Student Profile
        |--------------------------------------------------------------------------
        */

        DB::table('student_profiles')->updateOrInsert(
            ['user_id' => $studentId],
            [
                'nis' => '20260001',
                'class' => 'XII RPL 1',
                'major' => 'Rekayasa Perangkat Lunak',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Merchants
        |--------------------------------------------------------------------------
        */

        DB::table('merchants')->updateOrInsert(
            ['id' => $canteenMerchantId],
            [
                'owner_user_id' => $canteenUserId,
                'name' => 'Kantin Bu Ani',
                'type' => 'canteen',
                'description' => 'Kantin makanan dan minuman sekolah.',
                'logo_url' => null,
                'is_active' => true,
                'is_open' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('merchants')->updateOrInsert(
            ['id' => $cooperativeMerchantId],
            [
                'owner_user_id' => $cooperativeUserId,
                'name' => 'Koperasi Sekolah',
                'type' => 'cooperative',
                'description' => 'Kebutuhan alat tulis dan perlengkapan sekolah.',
                'logo_url' => null,
                'is_active' => true,
                'is_open' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        DB::table('categories')->updateOrInsert(
            ['id' => $foodCategoryId],
            [
                'merchant_id' => $canteenMerchantId,
                'name' => 'Makanan',
                'slug' => 'makanan',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => $drinkCategoryId],
            [
                'merchant_id' => $canteenMerchantId,
                'name' => 'Minuman',
                'slug' => 'minuman',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => $stationeryCategoryId],
            [
                'merchant_id' => $cooperativeMerchantId,
                'name' => 'Alat Tulis',
                'slug' => 'alat-tulis',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => $bookCategoryId],
            [
                'merchant_id' => $cooperativeMerchantId,
                'name' => 'Buku',
                'slug' => 'buku',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = [
            [
                'id' => '30000000-0000-4000-8000-000000000001',
                'merchant_id' => $canteenMerchantId,
                'category_id' => $foodCategoryId,
                'name' => 'Nasi Ayam Crispy',
                'slug' => 'nasi-ayam-crispy',
                'description' => 'Nasi hangat dengan ayam crispy dan sambal.',
                'price' => 15000,
                'stock' => 30,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000002',
                'merchant_id' => $canteenMerchantId,
                'category_id' => $foodCategoryId,
                'name' => 'Nasi Goreng',
                'slug' => 'nasi-goreng',
                'description' => 'Nasi goreng dengan telur dan kerupuk.',
                'price' => 13000,
                'stock' => 25,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000003',
                'merchant_id' => $canteenMerchantId,
                'category_id' => $foodCategoryId,
                'name' => 'Risol Mayo',
                'slug' => 'risol-mayo',
                'description' => 'Risol isi dengan saus mayo.',
                'price' => 5000,
                'stock' => 40,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000004',
                'merchant_id' => $canteenMerchantId,
                'category_id' => $drinkCategoryId,
                'name' => 'Es Teh',
                'slug' => 'es-teh',
                'description' => 'Es teh manis segar.',
                'price' => 4000,
                'stock' => 50,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000005',
                'merchant_id' => $cooperativeMerchantId,
                'category_id' => $bookCategoryId,
                'name' => 'Buku Tulis 38 Lembar',
                'slug' => 'buku-tulis-38-lembar',
                'description' => 'Buku tulis untuk kebutuhan belajar sehari-hari.',
                'price' => 5000,
                'stock' => 100,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000006',
                'merchant_id' => $cooperativeMerchantId,
                'category_id' => $stationeryCategoryId,
                'name' => 'Pulpen Hitam',
                'slug' => 'pulpen-hitam',
                'description' => 'Pulpen tinta hitam untuk kebutuhan sekolah.',
                'price' => 3000,
                'stock' => 80,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000007',
                'merchant_id' => $cooperativeMerchantId,
                'category_id' => $stationeryCategoryId,
                'name' => 'Pensil 2B',
                'slug' => 'pensil-2b',
                'description' => 'Pensil 2B untuk menulis dan menggambar.',
                'price' => 2500,
                'stock' => 75,
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000008',
                'merchant_id' => $cooperativeMerchantId,
                'category_id' => $stationeryCategoryId,
                'name' => 'Penggaris 30 cm',
                'slug' => 'penggaris-30-cm',
                'description' => 'Penggaris transparan untuk kebutuhan sekolah.',
                'price' => 4000,
                'stock' => 50,
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['id' => $product['id']],
                array_merge($product, [
                    'image_url' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pickup Slots
        |--------------------------------------------------------------------------
        */

        $pickupSlots = [
            [
                'id' => '40000000-0000-4000-8000-000000000001',
                'start_at' => today()->setTime(9, 30),
                'end_at' => today()->setTime(9, 45),
            ],
            [
                'id' => '40000000-0000-4000-8000-000000000002',
                'start_at' => today()->setTime(9, 45),
                'end_at' => today()->setTime(10, 0),
            ],
            [
                'id' => '40000000-0000-4000-8000-000000000003',
                'start_at' => today()->setTime(11, 30),
                'end_at' => today()->setTime(11, 45),
            ],
            [
                'id' => '40000000-0000-4000-8000-000000000004',
                'start_at' => today()->setTime(11, 45),
                'end_at' => today()->setTime(12, 0),
            ],
        ];

        foreach ($pickupSlots as $slot) {
            DB::table('pickup_slots')->updateOrInsert(
                ['id' => $slot['id']],
                [
                    'merchant_id' => $canteenMerchantId,
                    'start_at' => $slot['start_at'],
                    'end_at' => $slot['end_at'],
                    'capacity' => 20,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Student Wallet
        |--------------------------------------------------------------------------
        */

        DB::table('wallets')->updateOrInsert(
            ['user_id' => $studentId],
            [
                'id' => '50000000-0000-4000-8000-000000000001',
                'balance' => 100000,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}