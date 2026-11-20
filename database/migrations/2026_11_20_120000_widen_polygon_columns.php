<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `kebun` MODIFY `kebun_polygon` MEDIUMTEXT NULL');
        DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_polygon` MEDIUMTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `kebun` MODIFY `kebun_polygon` TEXT NULL');
        DB::statement('ALTER TABLE `kunjungan` MODIFY `kunjungan_polygon` TEXT NULL');
    }
};
