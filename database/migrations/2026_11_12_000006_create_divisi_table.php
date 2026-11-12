<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisi', function (Blueprint $table) {
            $table->increments('divisi_id');
            $table->string('divisi_kode', 20)->unique();
            $table->string('divisi_nama');
            $table->string('divisi_keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('kms_document_divisi', function (Blueprint $table) {
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('divisi_id');
            $table->primary(['document_id', 'divisi_id']);
            $table->foreign('document_id')->references('kms_document_id')->on('kms_documents')->cascadeOnDelete();
            $table->foreign('divisi_id')->references('divisi_id')->on('divisi')->cascadeOnDelete();
        });

        Schema::create('kms_document_tag', function (Blueprint $table) {
            $table->unsignedInteger('document_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['document_id', 'tag_id']);
            $table->foreign('document_id')->references('kms_document_id')->on('kms_documents')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('cms_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kms_document_tag');
        Schema::dropIfExists('kms_document_divisi');
        Schema::dropIfExists('divisi');
    }
};
