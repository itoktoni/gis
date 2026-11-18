<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absen', function (Blueprint $table) {
            if (! Schema::hasColumn('absen', 'latitude')) {
                $table->double('latitude')->nullable()->after('alamat');
            }
            if (! Schema::hasColumn('absen', 'longitude')) {
                $table->double('longitude')->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('absen', 'client_timestamp')) {
                $table->timestamp('client_timestamp')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absen', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'client_timestamp']);
        });
    }
};
