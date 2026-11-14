<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masalah', function (Blueprint $table) {
            $table->string('masalah_file', 255)->nullable()->after('masalah_rtl');
        });
    }

    public function down(): void
    {
        Schema::table('masalah', function (Blueprint $table) {
            $table->dropColumn('masalah_file');
        });
    }
};
