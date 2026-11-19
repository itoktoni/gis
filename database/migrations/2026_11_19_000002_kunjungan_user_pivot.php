<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungan_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('kunjungan_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['kunjungan_id', 'user_id']);

            $table->foreign('kunjungan_id')->references('kunjungan_id')->on('kunjungan')->cascadeOnDelete();
        });

        // Backfill: pemilik lama menjadi anggota pertama setiap titik.
        foreach (\App\Models\Kunjungan::all() as $k) {
            if ($k->kunjungan_id_user) {
                \Illuminate\Support\Facades\DB::table('kunjungan_user')->updateOrInsert(
                    ['kunjungan_id' => $k->kunjungan_id, 'user_id' => $k->kunjungan_id_user],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_user');
    }
};
