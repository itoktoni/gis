<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luas TM adalah data master kebun, bukan transaksi LHP
        Schema::table('lhp', function (Blueprint $table) {
            $table->dropColumn('lhp_luas_tm');
        });
    }

    public function down(): void
    {
        Schema::table('lhp', function (Blueprint $table) {
            $table->decimal('lhp_luas_tm', 10, 2)->nullable()->after('lhp_hari');
        });
    }
};
