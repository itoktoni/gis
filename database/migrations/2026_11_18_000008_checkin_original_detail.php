<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin', function (Blueprint $table) {
            if (! Schema::hasColumn('checkin', 'original_source')) {
                // Sumber dugaan lokasi asli: gps_raw | network | ip
                $table->string('original_source', 20)->nullable()->after('original_longitude');
            }
            if (! Schema::hasColumn('checkin', 'original_accuracy')) {
                $table->float('original_accuracy')->nullable()->after('original_source');
            }
            if (! Schema::hasColumn('checkin', 'original_distance_m')) {
                // Jarak titik check-in ke dugaan lokasi asli (meter)
                $table->float('original_distance_m')->nullable()->after('original_accuracy');
            }
            if (! Schema::hasColumn('checkin', 'original_map_url')) {
                $table->string('original_map_url', 500)->nullable()->after('original_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkin', function (Blueprint $table) {
            $table->dropColumn(['original_source', 'original_accuracy', 'original_distance_m', 'original_map_url']);
        });
    }
};
