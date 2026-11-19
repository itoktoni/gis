<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lhp_blok', function (Blueprint $table) {
            $table->increments('lhp_blok_id');
            $table->unsignedInteger('lhp_blok_id_lhp');
            $table->unsignedInteger('lhp_blok_id_blok');
            $table->decimal('lhp_blok_luas_panen', 10, 2)->nullable();
            $table->integer('lhp_blok_janjang_panen')->nullable();
            $table->integer('lhp_blok_janjang_kirim')->nullable();
            $table->decimal('lhp_blok_brondolan_panen', 12, 2)->nullable();
            $table->decimal('lhp_blok_brondolan_kirim', 12, 2)->nullable();
            $table->boolean('lhp_blok_restan')->default(false);
            $table->timestamps();

            $table->foreign('lhp_blok_id_lhp')->references('lhp_id')->on('lhp')->cascadeOnDelete();
            $table->foreign('lhp_blok_id_blok')->references('blok_id')->on('blok')->cascadeOnDelete();
            $table->unique(['lhp_blok_id_lhp', 'lhp_blok_id_blok']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lhp_blok');
    }
};
