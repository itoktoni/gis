<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Target & RKAP adalah data master kebun, bukan transaksi LHP
        Schema::table('lhp', function (Blueprint $table) {
            $table->dropColumn(['lhp_target_produksi_kg', 'lhp_produksi_rkap_kg']);
        });
    }

    public function down(): void
    {
        Schema::table('lhp', function (Blueprint $table) {
            $table->decimal('lhp_target_produksi_kg', 14, 2)->nullable()->after('lhp_produksi_panen_kg');
            $table->decimal('lhp_produksi_rkap_kg', 14, 2)->nullable()->after('lhp_target_produksi_kg');
        });
    }
};
