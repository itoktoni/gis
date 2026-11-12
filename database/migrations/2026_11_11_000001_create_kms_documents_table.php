<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kms_documents', function (Blueprint $table) {
            $table->increments('kms_document_id');
            $table->string('kms_document_judul');
            $table->text('kms_document_deskripsi')->nullable();
            $table->string('kms_document_file')->nullable();
            $table->string('kms_document_mime', 100)->nullable();
            $table->integer('kms_document_ukuran_kb')->default(0);
            $table->integer('kms_document_jumlah_halaman')->default(0);
            $table->integer('kms_document_jumlah_chunk')->default(0);
            $table->string('kms_document_status', 20)->default('processing');
            $table->text('kms_document_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kms_documents');
    }
};
