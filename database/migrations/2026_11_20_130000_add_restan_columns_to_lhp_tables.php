<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lhp_blok', function (Blueprint $table) {
            $table->integer('lhp_blok_restan_janjang')->nullable()->after('lhp_blok_brondolan_kirim');
            $table->decimal('lhp_blok_restan_brondolan', 12, 2)->nullable()->after('lhp_blok_restan_janjang');
        });
        Schema::table('lhp', function (Blueprint $table) {
            $table->integer('lhp_restan_janjang')->nullable()->after('lhp_brondolan_kg');
            $table->decimal('lhp_restan_brondolan_kg', 12, 2)->nullable()->after('lhp_restan_janjang');
        });
    }

    public function down(): void
    {
        Schema::table('lhp_blok', function (Blueprint $table) {
            $table->dropColumn(['lhp_blok_restan_janjang', 'lhp_blok_restan_brondolan']);
        });
        Schema::table('lhp', function (Blueprint $table) {
            $table->dropColumn(['lhp_restan_janjang', 'lhp_restan_brondolan_kg']);
        });
    }
};
