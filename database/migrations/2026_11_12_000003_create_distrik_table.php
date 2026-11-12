<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distrik', function (Blueprint $table) {
            $table->increments('distrik_id');
            $table->string('distrik_nama')->unique();
            $table->string('distrik_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::table('kebun', function (Blueprint $table) {
            $table->unsignedInteger('kebun_id_distrik')->nullable()->after('kebun_id_pt');
            $table->foreign('kebun_id_distrik')->references('distrik_id')->on('distrik')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kebun', function (Blueprint $table) {
            $table->dropForeign(['kebun_id_distrik']);
            $table->dropColumn('kebun_id_distrik');
        });

        Schema::dropIfExists('distrik');
    }
};
