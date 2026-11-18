<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absen', function (Blueprint $table) {
            if (! Schema::hasColumn('absen', 'jarak_m')) {
                // Jarak HP ke titik kunjungan (meter). null = tanpa titik.
                $table->float('jarak_m')->nullable()->after('accuracy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absen', function (Blueprint $table) {
            $table->dropColumn('jarak_m');
        });
    }
};
