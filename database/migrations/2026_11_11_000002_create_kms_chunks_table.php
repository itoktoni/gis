<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kms_chunks', function (Blueprint $table) {
            $table->increments('kms_chunk_id');
            $table->unsignedInteger('kms_chunk_id_document');
            $table->integer('kms_chunk_halaman')->nullable();
            $table->integer('kms_chunk_urutan')->default(0);
            $table->mediumText('kms_chunk_teks');
            $table->timestamps();

            $table->index('kms_chunk_id_document');
            $table->fullText('kms_chunk_teks');

            $table->foreign('kms_chunk_id_document')
                ->references('kms_document_id')
                ->on('kms_documents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kms_chunks');
    }
};
