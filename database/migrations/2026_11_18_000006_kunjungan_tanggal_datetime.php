<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kunjungan', 'kunjungan_tanggal')) {
            DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_tanggal` DATETIME NOT NULL');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_tanggal` DATE NOT NULL');
    }
};
