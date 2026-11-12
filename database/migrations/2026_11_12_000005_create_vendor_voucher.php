<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kebun', function (Blueprint $table) {
            $table->string('kebun_kelola', 20)->nullable()->after('kebun_status');
            $table->string('kebun_kabupaten', 50)->nullable()->after('kebun_kelola');
        });

        Schema::create('vendor', function (Blueprint $table) {
            $table->increments('vendor_id');
            $table->string('vendor_kode', 20)->unique();
            $table->string('vendor_nama');
            $table->string('vendor_jenis', 20)->default('perorangan');
            $table->string('vendor_telepon', 30)->nullable();
            $table->string('vendor_alamat')->nullable();
            $table->string('vendor_rekening', 100)->nullable();
            $table->string('vendor_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher', function (Blueprint $table) {
            $table->increments('voucher_id');
            $table->string('voucher_no')->unique();
            $table->unsignedInteger('voucher_id_vendor');
            $table->unsignedInteger('voucher_id_kebun')->nullable();
            $table->string('voucher_estate', 100)->nullable();
            $table->string('voucher_no_invoice')->nullable();
            $table->string('voucher_redaksi', 500)->nullable();
            $table->decimal('voucher_qty', 14, 2)->nullable();
            $table->string('voucher_satuan', 20)->nullable();
            $table->decimal('voucher_nilai', 16, 2)->default(0);
            $table->date('voucher_tanggal')->nullable();
            $table->string('voucher_status', 30)->default('draft');
            $table->string('voucher_file', 255)->nullable();
            $table->date('voucher_tanggal_bayar')->nullable();
            $table->string('voucher_bukti_file', 255)->nullable();
            $table->timestamps();

            $table->foreign('voucher_id_vendor')->references('vendor_id')->on('vendor')->cascadeOnDelete();
            $table->foreign('voucher_id_kebun')->references('kebun_id')->on('kebun')->nullOnDelete();
        });

        Schema::create('voucher_history', function (Blueprint $table) {
            $table->increments('voucher_history_id');
            $table->unsignedInteger('history_id_voucher');
            $table->string('history_status_dari', 30)->nullable();
            $table->string('history_status_ke', 30);
            $table->string('history_catatan', 500)->nullable();
            $table->string('history_file', 255)->nullable();
            $table->unsignedBigInteger('history_id_user')->nullable();
            $table->timestamps();

            $table->foreign('history_id_voucher')->references('voucher_id')->on('voucher')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_history');
        Schema::dropIfExists('voucher');

        Schema::table('kebun', function (Blueprint $table) {
            $table->dropColumn(['kebun_kelola', 'kebun_kabupaten']);
        });

        Schema::dropIfExists('vendor');
    }
};
