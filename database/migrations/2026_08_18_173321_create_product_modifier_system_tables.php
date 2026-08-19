<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Product Modifier Groups
        |--------------------------------------------------------------------------
        |
        | Contoh:
        | - Level Pedas
        | - Topping
        | - Bumbu
        | - Ukuran
        |
        */

        Schema::create(
            'product_modifier_groups',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->foreignUuid('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();

                $table->string(
                    'name',
                    100
                );

                /*
                 * single:
                 * hanya satu option.
                 *
                 * multiple:
                 * bisa lebih dari satu.
                 */
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

                $table->index([
                    'product_id',
                    'is_active',
                    'sort_order',
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Modifier Options
        |--------------------------------------------------------------------------
        |
        | Contoh Level Pedas:
        | - Tidak pedas +0
        | - Sedang      +0
        | - Pedas       +0
        |
        | Contoh Topping:
        | - Telur       +2000
        | - Keju        +2500
        |
        */

        Schema::create(
            'product_modifier_options',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->foreignUuid(
                    'modifier_group_id'
                )
                    ->constrained(
                        'product_modifier_groups'
                    )
                    ->cascadeOnDelete();

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

                $table->index([
                    'modifier_group_id',
                    'is_active',
                    'sort_order',
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Item Notes
        |--------------------------------------------------------------------------
        |
        | Order-level notes tetap dipertahankan.
        | Ini khusus catatan setiap cart/order item.
        |
        */

        Schema::table(
            'order_items',
            function (Blueprint $table) {
                $table->string(
                    'notes',
                    120
                )->nullable();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Order Item Modifier Snapshot
        |--------------------------------------------------------------------------
        |
        | Jangan bergantung pada data modifier aktif setelah order selesai.
        |
        | Kalau merchant mengubah:
        | "Telur +2000"
        | menjadi
        | "Telur +3000"
        |
        | order lama harus tetap menunjukkan +2000.
        |
        */

        Schema::create(
            'order_item_modifiers',
            function (Blueprint $table) {
                $table->uuid('id')
                    ->primary();

                $table->foreignUuid(
                    'order_item_id'
                )
                    ->constrained(
                        'order_items'
                    )
                    ->cascadeOnDelete();

                /*
                 * FK dibuat nullable karena snapshot
                 * harus tetap hidup jika modifier
                 * merchant suatu saat dihapus.
                 */

                $table->foreignUuid(
                    'modifier_group_id'
                )
                    ->nullable()
                    ->constrained(
                        'product_modifier_groups'
                    )
                    ->nullOnDelete();

                $table->foreignUuid(
                    'modifier_option_id'
                )
                    ->nullable()
                    ->constrained(
                        'product_modifier_options'
                    )
                    ->nullOnDelete();

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

                $table->index(
                    'order_item_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'order_item_modifiers'
        );

        Schema::table(
            'order_items',
            function (Blueprint $table) {
                $table->dropColumn(
                    'notes'
                );
            }
        );

        Schema::dropIfExists(
            'product_modifier_options'
        );

        Schema::dropIfExists(
            'product_modifier_groups'
        );
    }
};
