<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuti', function (Blueprint $table) {
            $table->id('cuti_id');
            $table->foreignId('cuti_id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('cuti_jenis', 50)->default('tahunan');
            $table->date('cuti_tanggal_mulai');
            $table->date('cuti_tanggal_selesai');
            $table->integer('cuti_jumlah_hari')->default(0);
            $table->text('cuti_keperluan');
            $table->string('cuti_status', 50)->default('pending');
            $table->foreignId('cuti_id_approver')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->dateTime('cuti_tanggal_approve')->nullable();
            $table->text('cuti_catatan')->nullable();
            $table->string('cuti_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuti');
    }
};
