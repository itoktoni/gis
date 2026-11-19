<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Status kunjungan boleh NULL (belum ditentukan) — sebelumnya default 'pending' not-null.
        DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_status` VARCHAR(20) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE `kunjungan` SET `kunjungan_status` = 'pending' WHERE `kunjungan_status` IS NULL");
        DB::statement("ALTER TABLE `kunjungan` MODIFY `kunjungan_status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }
};
