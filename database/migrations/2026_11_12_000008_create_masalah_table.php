<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masalah', function (Blueprint $table) {
            $table->increments('masalah_id');
            $table->unsignedInteger('masalah_id_kebun');
            $table->date('masalah_tanggal')->nullable();
            $table->string('masalah_permasalahan', 500);
            $table->text('masalah_kondisi')->nullable();
            $table->text('masalah_dampak')->nullable();
            $table->text('masalah_penyebab')->nullable();
            $table->text('masalah_rtl')->nullable();
            $table->string('masalah_status', 20)->default('open');
            $table->timestamps();

            $table->foreign('masalah_id_kebun')->references('kebun_id')->on('kebun')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masalah');
    }
};
