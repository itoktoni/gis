<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_template', function (Blueprint $table) {
            $table->id('surat_template_id');
            $table->string('surat_template_kode', 20)->unique();
            $table->string('surat_template_nama');
            $table->text('surat_template_isi');
            $table->string('surat_template_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('surat', function (Blueprint $table) {
            $table->id('surat_id');
            $table->string('surat_jenis', 10);
            $table->string('surat_kode_jenis', 20)->nullable();
            $table->string('surat_nomor')->unique()->nullable();
            $table->unsignedInteger('surat_urut')->nullable();
            $table->date('surat_tanggal');
            $table->unsignedInteger('surat_id_divisi')->nullable();
            $table->foreign('surat_id_divisi')->references('divisi_id')->on('divisi')->nullOnDelete();
            $table->foreignId('surat_id_template')->nullable()->constrained('surat_template', 'surat_template_id')->nullOnDelete();
            $table->string('surat_perihal')->nullable();
            $table->string('surat_dari')->nullable();
            $table->string('surat_kepada')->nullable();
            $table->json('surat_data')->nullable();
            $table->longText('surat_isi')->nullable();
            $table->string('surat_file')->nullable();
            $table->string('surat_keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
        Schema::dropIfExists('surat_template');
    }
};
