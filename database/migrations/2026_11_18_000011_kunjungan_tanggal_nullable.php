<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_tanggal` DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_tanggal` DATETIME NOT NULL');
    }
};
