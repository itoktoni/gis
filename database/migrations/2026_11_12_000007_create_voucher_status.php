<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_status', function (Blueprint $table) {
            $table->increments('voucher_status_id');
            $table->string('voucher_status_kode', 30)->unique();
            $table->string('voucher_status_nama');
            $table->integer('voucher_status_no')->nullable();
            $table->string('voucher_status_keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_status');
    }
};
