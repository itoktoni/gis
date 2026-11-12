<?php

namespace Database\Seeders;

use App\Models\Kebun;
use App\Models\Lhp;
use App\Models\Pt;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LhpSeeder extends Seeder
{
    public function run(): void
    {
        $pts = Pt::pluck('pt_id', 'pt_nama');

        $kebuns = [
            ['kebun_nama' => 'Kebun A Natai', 'pt' => 'PT Sawit Agro Lestari', 'luas' => 1853.95],
            ['kebun_nama' => 'Kebun B Kumai', 'pt' => 'PT Sawit Agro Lestari', 'luas' => 1208.40],
            ['kebun_nama' => 'Kebun C Lamandau', 'pt' => 'PT Borneo Tani Makmur', 'luas' => 2008.00],
            ['kebun_nama' => 'Kebun D Seruyan', 'pt' => 'PT Borneo Tani Makmur', 'luas' => 957.30],
            ['kebun_nama' => 'Kebun E Koperasi', 'pt' => 'Koperasi Tani Maju Bersama', 'luas' => 258.00],
            ['kebun_nama' => 'Kebun F Tanpa PT', 'pt' => null, 'luas' => 412.75],
        ];

        foreach ($kebuns as $k) {
            Kebun::updateOrCreate(
                ['kebun_nama' => $k['kebun_nama']],
                [
                    'kebun_id_pt' => $k['pt'] ? ($pts[$k['pt']] ?? null) : null,
                    'kebun_luas_ha' => $k['luas'],
                    'kebun_status' => 'aktif',
                ]
            );
        }

        // LHP 14 hari terakhir per kebun (acak terkendali, ada hari kosong)
        $ids = Kebun::whereIn('kebun_nama', array_column($kebuns, 'kebun_nama'))->pluck('kebun_id', 'kebun_nama');

        foreach ($kebuns as $index => $k) {
            $kebunId = $ids[$k['kebun_nama']];
            $base = 2500 + ($index * 700);

            for ($d = 13; $d >= 0; $d--) {
                if (($d + $index) % 7 === 0) {
                    continue; // simulasi hari tanpa laporan
                }

                $tanggal = Carbon::today()->subDays($d)->toDateString();
                $faktor = 0.7 + ((($d * 13 + $index * 29) % 60) / 100); // 0.70 - 1.29
                $janjang = (int) round($base * $faktor);
                $bjr = 14 + (($d + $index) % 5) * 0.4; // 14.0 - 15.6
                $produksi = (int) round($janjang * $bjr);
                $kirim = (int) round($janjang * (0.92 + (($d % 4) / 100)));
                $tk = (int) round(40 + $index * 8 + ($d % 5) * 3);
                $luasPanen = round($k['luas'] * (0.05 + (($d * 7 + $index) % 10) / 200), 2);

                Lhp::updateOrCreate(
                    ['lhp_id_kebun' => $kebunId, 'lhp_tanggal' => $tanggal],
                    [
                        'lhp_hari' => Carbon::parse($tanggal)->locale('id')->isoFormat('dddd'),
                        'lhp_luas_tm' => $k['luas'],
                        'lhp_luas_panen' => $luasPanen,
                        'lhp_tk_hk' => $tk,
                        'lhp_janjang_panen' => $janjang,
                        'lhp_produksi_panen_kg' => $produksi,
                        'lhp_janjang_kirim' => $kirim,
                        'lhp_produksi_kirim_kg' => (int) round($kirim * $bjr),
                    ]
                );
            }
        }
    }
}
