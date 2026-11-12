<?php

namespace Database\Seeders;

use App\Models\Divisi;
use Illuminate\Database\Seeder;

class DivisiSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['divisi_kode' => 'KEU', 'divisi_nama' => 'Keuangan', 'divisi_keterangan' => 'Akuntansi, kasir, AP/AR'],
            ['divisi_kode' => 'SDM', 'divisi_nama' => 'SDM & Umum', 'divisi_keterangan' => 'Personalia, GA, Humas'],
            ['divisi_kode' => 'PBJ', 'divisi_nama' => 'Pengadaan', 'divisi_keterangan' => 'Procurement barang/jasa'],
            ['divisi_kode' => 'TNM', 'divisi_nama' => 'Tanaman', 'divisi_keterangan' => 'Agronomi, produksi kebun'],
            ['divisi_kode' => 'TKS', 'divisi_nama' => 'Traksi & Mekanisasi', 'divisi_keterangan' => 'Alat berat, kendaraan, bengkel'],
            ['divisi_kode' => 'KAM', 'divisi_nama' => 'Keamanan', 'divisi_keterangan' => 'Security, hubungan kelembagaan'],
            ['divisi_kode' => 'KSO', 'divisi_nama' => 'Kinerja KSO', 'divisi_keterangan' => 'Monitoring mitra KSO'],
            ['divisi_kode' => 'PBR', 'divisi_nama' => 'Pabrik', 'divisi_keterangan' => 'PKS / pengolahan'],
        ];

        foreach ($rows as $row) {
            Divisi::updateOrCreate(['divisi_kode' => $row['divisi_kode']], $row);
        }
    }
}
