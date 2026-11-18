<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('validation_log')) {
            Schema::create('validation_log', function (Blueprint $table) {
                $table->id();
                $table->uuid('signature_id')->nullable();
                $table->string('result', 50);
                $table->string('ip', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('crypto_key', 'fingerprint')) {
            Schema::table('crypto_key', function (Blueprint $table) {
                $table->string('fingerprint', 64)->nullable()->after('public_key');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_log');
    }
};
