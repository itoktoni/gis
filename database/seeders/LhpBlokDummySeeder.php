<?php

namespace Database\Seeders;

use App\Models\Blok;
use App\Models\Lhp;
use App\Services\LhpBlokService;
use Illuminate\Database\Seeder;

/**
 * Dummy rincian panen per blok (lhp_blok) untuk LHP kebun 1
 * pada rentang 1 Nov 2026 s/d hari ini, agar report per blok ada datanya.
 * Memakai LhpBlokService::sync supaya total header ikut terhitung ulang.
 *
 * Jalankan: php artisan db:seed --class=LhpBlokDummySeeder
 */
class LhpBlokDummySeeder extends Seeder
{
    public function run(): void
    {
        $kebunId = 1;
        $dari = '2026-11-01';
        $sampai = now()->toDateString();

        mt_srand(20261101);

        $blokIds = Blok::where('blok_id_kebun', $kebunId)->orderBy('blok_kode')->pluck('blok_id')->all();
        if ($blokIds === []) {
            $this->command?->error("Tidak ada blok untuk kebun_id={$kebunId}");

            return;
        }

        $lhps = Lhp::where('lhp_id_kebun', $kebunId)
            ->whereDate('lhp_tanggal', '>=', $dari)
            ->whereDate('lhp_tanggal', '<=', $sampai)
            ->orderBy('lhp_tanggal')
            ->get();

        if ($lhps->isEmpty()) {
            $this->command?->error("Tidak ada LHP kebun_id={$kebunId} pada {$dari} s/d {$sampai}");

            return;
        }

        $svc = app(LhpBlokService::class);
        $n = 0;
        foreach ($lhps as $lhp) {
            // 2-4 blok dipanen per hari (acak, rotasi supaya pusingan bervariasi).
            $count = mt_rand(2, min(4, count($blokIds)));
            $picked = (array) array_rand(array_flip($blokIds), $count);
            $rows = [];
            foreach ($picked as $blokId) {
                $jp = mt_rand(600, 1500);
                $jk = $jp - mt_rand(0, (int) ($jp * 0.15)); // kirim <= panen
                $bp = round(mt_rand(15000, 45000) / 100, 2); // kg brondolan panen
                $bk = round($bp - mt_rand(0, (int) ($bp * 0.15 * 100)) / 100, 2);
                $rows[$blokId] = [
                    'luas_panen' => round(mt_rand(800, 2500) / 100, 2),
                    'janjang_panen' => $jp,
                    'janjang_kirim' => $jk,
                    'brondolan_panen' => $bp,
                    'brondolan_kirim' => max(0, $bk),
                    'restan' => false, // status restan dihitung otomatis (sisa > 0)
                ];
            }
            $svc->sync($lhp, $rows);
            $n += count($rows);
        }

        $this->command?->info("OK: {$n} baris lhp_blok untuk {$lhps->count()} LHP (kebun {$kebunId}, {$dari} s/d {$sampai}).");
    }
}
