<?php

namespace Database\Seeders;

use App\Models\Distrik;
use App\Models\Kebun;
use Illuminate\Database\Seeder;

class DistrikSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['distrik_kode' => 'D01', 'distrik_nama' => 'Distrik 1 Kotawaringin', 'distrik_keterangan' => 'Distrik dummy 1'],
            ['distrik_kode' => 'D02', 'distrik_nama' => 'Distrik 2 Lamandau', 'distrik_keterangan' => 'Distrik dummy 2'],
            ['distrik_kode' => 'D03', 'distrik_nama' => 'Distrik 3 Seruyan', 'distrik_keterangan' => 'Distrik dummy 3'],
        ];

        foreach ($rows as $row) {
            Distrik::updateOrCreate(['distrik_nama' => $row['distrik_nama']], $row);
        }

        // Petakan kebun dummy ke distrik
        $ids = Distrik::pluck('distrik_id', 'distrik_kode');
        $map = [
            'Kebun A Natai' => 'D01',
            'Kebun B Kumai' => 'D01',
            'Kebun C Lamandau' => 'D02',
            'Kebun D Seruyan' => 'D03',
            'Kebun E Koperasi' => 'D02',
        ];

        foreach ($map as $nama => $kode) {
            Kebun::where('kebun_nama', $nama)->update(['kebun_id_distrik' => $ids[$kode] ?? null]);
        }
    }
}
