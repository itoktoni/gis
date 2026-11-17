<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lhp', function (Blueprint $table) {
            // Snapshot dari master kebun (auto-fill saat create, tetap bisa diedit)
            $table->decimal('lhp_target_produksi_kg', 14, 2)->nullable()->after('lhp_produksi_panen_kg');
            $table->decimal('lhp_produksi_rkap_kg', 14, 2)->nullable()->after('lhp_target_produksi_kg');
            // Input user harian
            $table->decimal('lhp_brondolan_kg', 14, 2)->nullable()->after('lhp_produksi_kirim_kg');
            $table->decimal('lhp_up_le10', 10, 2)->nullable();
            $table->decimal('lhp_up_11_15', 10, 2)->nullable();
            $table->decimal('lhp_up_gt15', 10, 2)->nullable();
            $table->decimal('lhp_hujan_pagi', 8, 2)->nullable();
            $table->decimal('lhp_hujan_siang', 8, 2)->nullable();
            $table->decimal('lhp_hujan_malam', 8, 2)->nullable();
            $table->string('lhp_blok_restan', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lhp', function (Blueprint $table) {
            $table->dropColumn([
                'lhp_target_produksi_kg',
                'lhp_produksi_rkap_kg',
                'lhp_brondolan_kg',
                'lhp_up_le10',
                'lhp_up_11_15',
                'lhp_up_gt15',
                'lhp_hujan_pagi',
                'lhp_hujan_siang',
                'lhp_hujan_malam',
                'lhp_blok_restan',
            ]);
        });
    }
};
