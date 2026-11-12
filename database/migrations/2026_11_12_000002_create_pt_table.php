<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pt', function (Blueprint $table) {
            $table->increments('pt_id');
            $table->string('pt_nama')->unique();
            $table->string('pt_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::table('kebun', function (Blueprint $table) {
            $table->unsignedInteger('kebun_id_pt')->nullable()->after('kebun_nama');
            $table->foreign('kebun_id_pt')->references('pt_id')->on('pt')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kebun', function (Blueprint $table) {
            $table->dropForeign(['kebun_id_pt']);
            $table->dropColumn('kebun_id_pt');
        });

        Schema::dropIfExists('pt');
    }
};
