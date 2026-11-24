<?php

namespace Database\Seeders;

use App\Models\Blok;
use App\Models\Kebun;
use App\Models\Lhp;
use App\Models\LhpBlok;
use App\Services\LhpBlokService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Dummy transaksi LHP + rincian blok untuk demo pusingan.
 *
 * Skenario (Kebun A Natai):
 * - H-10 : A1, A2 dipanen (keduanya perdana)
 * - H-7  : A1 dipanen lagi (pusingan 3), B1 perdana
 * - H-3  : A1 (pusingan 4), A2 (pusingan 7)
 * - H-0  : A1 (pusingan 3), B1 (pusingan 7)
 */
class DummyPusinganSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan rincian blok dummy lama agar histori sesuai skenario.
        LhpBlok::query()->delete();

        $kebun = Kebun::where('kebun_nama', 'Kebun A Natai')->first() ?? Kebun::firstOrFail();
        $blokId = Blok::where('blok_id_kebun', $kebun->kebun_id)->pluck('blok_id', 'blok_kode')->all();

        foreach (['A1', 'A2', 'B1', 'B2'] as $kode) {
            if (! isset($blokId[$kode])) {
                $b = Blok::create([
                    'blok_id_kebun' => $kebun->kebun_id,
                    'blok_kode' => $kode,
                    'blok_nama' => 'Blok '.$kode.' - '.$kebun->kebun_nama,
                    'blok_luas_ha' => 30,
                    'blok_jumlah_pohon' => 1500,
                ]);
                $blokId[$kode] = $b->blok_id;
            }
        }

        // [hari_lalu => [kode_blok => [luas, jj_panen, jj_kirim, brd_panen, brd_kirim]]]
        $plan = [
            10 => [
                'A1' => [12.5, 1250, 1200, 320.5, 300.0],
                'A2' => [10.0, 980, 950, 250.0, 240.5],
            ],
            7 => [
                'A1' => [11.0, 1100, 1080, 290.0, 285.0],
                'B1' => [30.0, 1500, 1450, 400.0, 390.0],
            ],
            3 => [
                'A1' => [12.0, 1300, 1250, 340.0, 320.0],
                'A2' => [9.5, 900, 880, 230.0, 225.0],
            ],
            0 => [
                'A1' => [12.5, 1280, 1200, 335.0, 310.0],
                'B1' => [28.5, 1450, 1400, 380.0, 370.0],
            ],
        ];

        $svc = app(LhpBlokService::class);

        foreach ($plan as $ago => $blocks) {
            $tanggal = Carbon::today()->subDays($ago)->toDateString();

            $lhp = Lhp::updateOrCreate(
                ['lhp_id_kebun' => $kebun->kebun_id, 'lhp_tanggal' => $tanggal],
                ['lhp_hari' => Carbon::parse($tanggal)->locale('id')->isoFormat('dddd')]
            );

            $rows = [];
            $tk = 0;
            foreach ($blocks as $kode => [$luas, $jp, $jk, $bp, $bk]) {
                $rows[$blokId[$kode]] = [
                    'luas_panen' => $luas,
                    'janjang_panen' => $jp,
                    'janjang_kirim' => $jk,
                    'brondolan_panen' => $bp,
                    'brondolan_kirim' => $bk,
                ];
                $tk += 8;
            }
            $svc->sync($lhp, $rows);

            // Angka header produksi (hasil timbang) — deterministik dari rincian.
            $sumJp = array_sum(array_column($blocks, 1));
            $lhp->update([
                'lhp_tk_hk' => $tk,
                'lhp_produksi_panen_kg' => (int) round($sumJp * 14.8),
                'lhp_produksi_kirim_kg' => (int) round(array_sum(array_column($blocks, 2)) * 14.8),
            ]);
        }
    }
}
