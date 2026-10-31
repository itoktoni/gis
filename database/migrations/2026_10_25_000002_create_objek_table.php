<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objek', function (Blueprint $table) {
            $table->increments('objek_id');
            $table->string('objek_nama');
            $table->string('objek_jenis')->default('pos_pantau');
            $table->double('objek_lat')->nullable();
            $table->double('objek_lng')->nullable();
            $table->unsignedInteger('objek_id_kebun')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objek');
    }
};
