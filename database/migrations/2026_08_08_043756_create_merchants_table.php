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
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'));

            $table->uuid('owner_user_id');

            $table->string('name');
            $table->string('type', 30);

            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_open')->default(true);

            $table->timestamps();

            $table->foreign('owner_user_id')
                ->references('id')
                ->on('profiles')
                ->cascadeOnDelete();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
