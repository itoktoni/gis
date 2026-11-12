<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pt', function (Blueprint $table) {
            $table->string('pt_kode', 20)->nullable()->unique()->after('pt_id');
        });

        Schema::table('distrik', function (Blueprint $table) {
            $table->string('distrik_kode', 20)->nullable()->unique()->after('distrik_id');
        });
    }

    public function down(): void
    {
        Schema::table('pt', function (Blueprint $table) {
            $table->dropUnique(['pt_kode']);
            $table->dropColumn('pt_kode');
        });

        Schema::table('distrik', function (Blueprint $table) {
            $table->dropUnique(['distrik_kode']);
            $table->dropColumn('distrik_kode');
        });
    }
};
