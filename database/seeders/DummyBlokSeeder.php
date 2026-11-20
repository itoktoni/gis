<?php

namespace Database\Seeders;

use App\Models\Blok;
use App\Models\Kebun;
use Illuminate\Database\Seeder;

class DummyBlokSeeder extends Seeder
{
    public function run(): void
    {
        $kode = ['A1', 'A2', 'B1', 'B2'];

        $kebuns = Kebun::whereNotIn('kebun_id', Blok::select('blok_id_kebun'))->get();

        foreach ($kebuns as $i => $kebun) {
            foreach ($kode as $j => $k) {
                Blok::create([
                    'blok_id_kebun' => $kebun->kebun_id,
                    'blok_kode' => $k,
                    'blok_nama' => 'Blok '.$k.' - '.$kebun->kebun_nama,
                    'blok_luas_ha' => 10 + (($i + $j) % 20),
                    'blok_jumlah_pohon' => 1000 + (($i * 4 + $j) * 37) % 2000,
                ]);
            }
        }
    }
}
