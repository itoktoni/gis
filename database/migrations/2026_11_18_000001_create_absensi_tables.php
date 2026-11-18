<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('checkin_secret', 64)->nullable()->after('remember_token');
        });

        Schema::create('crypto_key', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('public_key');
            $table->text('private_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('signature', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('crypto_key_id')->nullable();
            $table->string('type', 20);
            $table->string('label')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('content_hash', 64);
            $table->text('embedded_signature');
            $table->string('status', 20)->default('active');
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('signature_string', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('signature_id');
            $table->string('original_string', 500);
            $table->timestamps();
        });

        Schema::create('checkin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('signature_id')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->double('latitude');
            $table->double('longitude');
            $table->float('accuracy')->nullable();
            $table->string('address', 500)->nullable();
            $table->timestamp('client_timestamp')->nullable();
            $table->string('photo_path');
            $table->string('status', 20)->default('valid');
            $table->text('failure_reason')->nullable();
            $table->longText('payload_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_offline')->default(false);
            $table->double('original_latitude')->nullable();
            $table->double('original_longitude')->nullable();
            $table->string('original_address', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('kunjungan', function (Blueprint $table) {
            $table->uuid('kunjungan_id')->primary();
            $table->foreignId('kunjungan_id_user')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('kunjungan_id_objek')->nullable();
            $table->unsignedBigInteger('kunjungan_id_kebun')->nullable();
            $table->date('kunjungan_tanggal');
            $table->string('kunjungan_nama');
            $table->double('kunjungan_lat');
            $table->double('kunjungan_lng');
            $table->integer('kunjungan_radius_m')->default(100);
            $table->string('kunjungan_status', 20)->default('pending');
            $table->uuid('kunjungan_id_checkin')->nullable();
            $table->timestamps();
            $table->index(['kunjungan_id_user', 'kunjungan_tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan');
        Schema::dropIfExists('checkin');
        Schema::dropIfExists('signature_string');
        Schema::dropIfExists('signature');
        Schema::dropIfExists('crypto_key');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('checkin_secret');
        });
    }
};
