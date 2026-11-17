<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuti', function (Blueprint $table) {
            $table->string('cuti_bagian', 100)->nullable()->after('cuti_id_user');
            $table->string('cuti_alamat', 255)->nullable()->after('cuti_keperluan');
        });
    }

    public function down(): void
    {
        Schema::table('cuti', function (Blueprint $table) {
            $table->dropColumn(['cuti_bagian', 'cuti_alamat']);
        });
    }
};
