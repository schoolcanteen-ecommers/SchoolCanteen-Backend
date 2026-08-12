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
        Schema::create('merchant_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'));

            $table->uuid('merchant_wallet_id');

            $table->string('type', 40);
            $table->string('direction', 10);

            $table->unsignedBigInteger('amount');

            $table->string('status', 20)
                ->default('completed');

            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->foreign('merchant_wallet_id')
                ->references('id')
                ->on('merchant_wallets')
                ->cascadeOnDelete();

            $table->index('type');

            $table->index([
                'reference_type',
                'reference_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_wallet_transactions');
    }
};
