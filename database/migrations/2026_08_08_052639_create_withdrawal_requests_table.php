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
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->uuid('id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'));

            $table->uuid('merchant_id');

            $table->uuid('payment_account_id')
                ->nullable();

            $table->uuid('approved_by')
                ->nullable();

            $table->unsignedBigInteger('amount');

            $table->string('method', 30);

            $table->string('status', 30)
                ->default('waiting');

            $table->text('notes')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->restrictOnDelete();

            $table->foreign('payment_account_id')
                ->references('id')
                ->on('merchant_payment_accounts')
                ->nullOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('profiles')
                ->nullOnDelete();

            $table->index('status');
            $table->index('method');
            $table->index('merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
