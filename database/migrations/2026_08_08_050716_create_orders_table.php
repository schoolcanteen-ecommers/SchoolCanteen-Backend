<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'));

            $table->uuid('user_id');
            $table->uuid('merchant_id');
            $table->uuid('pickup_slot_id')->nullable();

            $table->string('order_code', 50)->unique();

            $table->string('status', 30)
                ->default('waiting');

            $table->unsignedBigInteger('total_amount');

            $table->text('notes')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('profiles')
                ->restrictOnDelete();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->restrictOnDelete();

            $table->foreign('pickup_slot_id')
                ->references('id')
                ->on('pickup_slots')
                ->nullOnDelete();

            $table->index('status');
            $table->index('user_id');
            $table->index('merchant_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
