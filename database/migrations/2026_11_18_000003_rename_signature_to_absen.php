<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('signature') && ! Schema::hasTable('absen')) {
            Schema::rename('signature', 'absen');
        }

        Schema::table('absen', function (Blueprint $table) {
            if (! Schema::hasColumn('absen', 'mock')) {
                $table->boolean('mock')->default(false)->after('embedded_signature');
            }
            if (! Schema::hasColumn('absen', 'developer')) {
                $table->boolean('developer')->default(false)->after('mock');
            }
            if (! Schema::hasColumn('absen', 'alamat')) {
                $table->string('alamat', 500)->nullable()->after('developer');
            }
            if (! Schema::hasColumn('absen', 'photo')) {
                $table->string('photo', 255)->nullable()->after('alamat');
            }
            if (! Schema::hasColumn('absen', 'accuracy')) {
                $table->float('accuracy')->nullable()->after('photo');
            }
            if (! Schema::hasColumn('absen', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('accuracy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absen', function (Blueprint $table) {
            $table->dropColumn(['mock', 'developer', 'alamat', 'photo', 'accuracy', 'ip_address']);
        });
        Schema::rename('absen', 'signature');
    }
};
