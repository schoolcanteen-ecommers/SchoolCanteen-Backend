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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->text('avatar_url')->nullable();

            $table->string('role', 20)->default('student');

            $table->timestamps();

            $table->index('role');
        });

        DB::statement('
            ALTER TABLE profiles
            ADD CONSTRAINT profiles_id_foreign
            FOREIGN KEY (id)
            REFERENCES auth.users(id)
            ON DELETE CASCADE
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
