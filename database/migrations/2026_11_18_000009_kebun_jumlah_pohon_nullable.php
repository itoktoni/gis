<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `kebun` MODIFY `kebun_jumlah_pohon` INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `kebun` MODIFY `kebun_jumlah_pohon` INT NOT NULL DEFAULT 0');
    }
};
