<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kebun', function (Blueprint $table) {
            $table->decimal('kebun_target_produksi_kg', 14, 2)->nullable()->after('kebun_luas_ha');
            $table->decimal('kebun_rkap_kg', 14, 2)->nullable()->after('kebun_target_produksi_kg');
        });
    }

    public function down(): void
    {
        Schema::table('kebun', function (Blueprint $table) {
            $table->dropColumn(['kebun_target_produksi_kg', 'kebun_rkap_kg']);
        });
    }
};
