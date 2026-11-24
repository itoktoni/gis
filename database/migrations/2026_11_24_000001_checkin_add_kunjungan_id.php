<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin', function (Blueprint $table) {
            // Pembedakan Checkin biasa vs Kunjungan (titik kunjungan).
            if (! Schema::hasColumn('checkin', 'kunjungan_id')) {
                $table->string('kunjungan_id')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkin', function (Blueprint $table) {
            $table->dropColumn(['kunjungan_id']);
        });
    }
};
