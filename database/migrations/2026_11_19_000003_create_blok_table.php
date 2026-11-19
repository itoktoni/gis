<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blok', function (Blueprint $table) {
            $table->increments('blok_id');
            $table->unsignedInteger('blok_id_kebun');
            $table->string('blok_kode', 20);
            $table->string('blok_nama');
            $table->decimal('blok_luas_ha', 10, 2)->nullable();
            $table->integer('blok_jumlah_pohon')->default(0);
            $table->timestamps();

            $table->foreign('blok_id_kebun')->references('kebun_id')->on('kebun')->cascadeOnDelete();
            $table->unique(['blok_id_kebun', 'blok_kode']);
            $table->index('blok_id_kebun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blok');
    }
};
