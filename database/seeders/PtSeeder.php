<?php

namespace Database\Seeders;

use App\Models\Pt;
use Illuminate\Database\Seeder;

class PtSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['pt_kode' => 'PT01', 'pt_nama' => 'PT Sawit Agro Lestari', 'pt_keterangan' => 'Grup dummy 1'],
            ['pt_kode' => 'PT02', 'pt_nama' => 'PT Borneo Tani Makmur', 'pt_keterangan' => 'Grup dummy 2'],
            ['pt_kode' => 'KOP01', 'pt_nama' => 'Koperasi Tani Maju Bersama', 'pt_keterangan' => 'Koperasi dummy'],
        ];

        foreach ($rows as $row) {
            Pt::updateOrCreate(['pt_nama' => $row['pt_nama']], $row);
        }
    }
}
