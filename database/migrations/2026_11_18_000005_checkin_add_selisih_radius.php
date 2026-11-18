<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin', function (Blueprint $table) {
            if (! Schema::hasColumn('checkin', 'selisih_radius_m')) {
                // Perkiraan selisih jarak ke titik kunjungan (meter).
                // Negatif = di dalam radius, positif = di luar radius, null = tanpa titik.
                $table->float('selisih_radius_m')->nullable()->after('accuracy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkin', function (Blueprint $table) {
            $table->dropColumn('selisih_radius_m');
        });
    }
};
