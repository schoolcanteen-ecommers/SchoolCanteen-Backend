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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'));

            $table->uuid('wallet_transaction_id');

            $table->string('provider', 30)
                ->default('midtrans');

            $table->string('provider_order_id')->unique();

            $table->string('provider_transaction_id')
                ->nullable()
                ->unique();

            $table->string('payment_type', 50)
                ->nullable();

            $table->string('status', 30)
                ->default('pending');

            $table->unsignedBigInteger('gross_amount');

            $table->jsonb('provider_response')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->timestamps();

            $table->foreign('wallet_transaction_id')
                ->references('id')
                ->on('wallet_transactions')
                ->cascadeOnDelete();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
