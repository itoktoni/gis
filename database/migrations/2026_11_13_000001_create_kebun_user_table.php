<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kebun_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('kebun_id');
            $table->foreign('kebun_id')->references('kebun_id')->on('kebun')->cascadeOnDelete();
            $table->unique(['user_id', 'kebun_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kebun_user');
    }
};
