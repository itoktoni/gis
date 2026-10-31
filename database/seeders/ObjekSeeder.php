<?php

namespace Database\Seeders;

use App\Models\Kebun;
use App\Models\Objek;
use Illuminate\Database\Seeder;

class ObjekSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['nama' => 'POS Pantau 1 Siak', 'jenis' => 'pos_pantau', 'kebun' => 'Blok A Sungai Siak', 'lat' => 0.5502, 'lng' => 101.4505],
            ['nama' => 'Gudang Siak', 'jenis' => 'gudang', 'kebun' => 'Blok A Sungai Siak', 'lat' => 0.5490, 'lng' => 101.4485],
            ['nama' => 'POS Pantau Mangkei', 'jenis' => 'pos_pantau', 'kebun' => 'Blok B Sei Mangkei', 'lat' => 3.1202, 'lng' => 99.35],
        ];

        foreach ($rows as $row) {
            $kebun = Kebun::where('kebun_nama', $row['kebun'])->first();
            Objek::updateOrCreate(
                ['objek_nama' => $row['nama']],
                [
                    'objek_jenis' => $row['jenis'],
                    'objek_lat' => $row['lat'],
                    'objek_lng' => $row['lng'],
                    'objek_id_kebun' => $kebun?->kebun_id,
                ]
            );
        }
    }
}
