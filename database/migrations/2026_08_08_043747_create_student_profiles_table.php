<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->uuid('user_id')->primary();

            $table->string('nis', 50)->unique();
            $table->string('class', 50);
            $table->string('major', 100)->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('profiles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
