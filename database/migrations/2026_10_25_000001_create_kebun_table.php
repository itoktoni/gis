<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kebun', function (Blueprint $table) {
            $table->increments('kebun_id');
            $table->string('kebun_nama');
            $table->decimal('kebun_luas_ha', 10, 2)->nullable();
            $table->integer('kebun_jumlah_pohon')->default(0);
            $table->string('kebun_status')->default('aktif');
            $table->double('kebun_lat')->nullable();
            $table->double('kebun_lng')->nullable();
            $table->text('kebun_polygon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kebun');
    }
};
