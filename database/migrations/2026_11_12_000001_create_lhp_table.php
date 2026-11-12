<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lhp', function (Blueprint $table) {
            $table->increments('lhp_id');
            $table->unsignedInteger('lhp_id_kebun');
            $table->date('lhp_tanggal');
            $table->string('lhp_hari', 15)->nullable();
            $table->decimal('lhp_luas_tm', 10, 2)->nullable();
            $table->decimal('lhp_luas_panen', 10, 2)->nullable();
            $table->decimal('lhp_tk_hk', 10, 2)->nullable();
            $table->integer('lhp_janjang_panen')->nullable();
            $table->decimal('lhp_produksi_panen_kg', 14, 2)->nullable();
            $table->integer('lhp_janjang_kirim')->nullable();
            $table->decimal('lhp_produksi_kirim_kg', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['lhp_id_kebun', 'lhp_tanggal'], 'lhp_kebun_tanggal_unique');
            $table->foreign('lhp_id_kebun')->references('kebun_id')->on('kebun')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lhp');
    }
};
