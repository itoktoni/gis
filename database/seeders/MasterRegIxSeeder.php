<?php

namespace Database\Seeders;

use App\Models\Distrik;
use App\Models\Kebun;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class MasterRegIxSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->distriks() as $row) {
            Distrik::updateOrCreate(['distrik_kode' => $row['distrik_kode']], $row);
        }
        $distrikIds = Distrik::pluck('distrik_id', 'distrik_kode');

        foreach ($this->kebuns() as $row) {
            Kebun::updateOrCreate(
                ['kebun_nama' => $row['nama']],
                [
                    'kebun_id_distrik' => $distrikIds[$row['distrik']] ?? null,
                    'kebun_kabupaten' => $row['kab'],
                    'kebun_kelola' => $row['kelola'],
                    'kebun_luas_ha' => $row['luas'],
                    'kebun_status' => 'aktif',
                ]
            );
        }

        $n = 0;
        foreach ($this->vendors() as $nama) {
            $n++;
            Vendor::updateOrCreate(
                ['vendor_nama' => $nama],
                [
                    'vendor_kode' => 'VND'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                    'vendor_jenis' => $this->jenis($nama),
                    'vendor_keterangan' => 'Seeder Buku Pintar Reg IX / VGuard',
                ]
            );
        }
    }

    private function jenis(string $nama): string
    {
        $upper = strtoupper($nama);
        if (str_starts_with($upper, 'PT')) {
            return 'pt';
        }
        if (str_starts_with($upper, 'CV')) {
            return 'cv';
        }
        if (str_starts_with($upper, 'KOP') || str_starts_with($upper, 'KUD') || str_contains($upper, 'KOPERASI')) {
            return 'koperasi';
        }

        return 'perorangan';
    }

    private function distriks(): array
    {
        return [
            ['distrik_kode' => 'D01', 'distrik_nama' => 'Distrik 1', 'distrik_keterangan' => 'Seruyan, Kotawaringin Barat'],
            ['distrik_kode' => 'D02', 'distrik_nama' => 'Distrik 2', 'distrik_keterangan' => 'Sukamara, Kotawaringin Barat, Lamandau'],
            ['distrik_kode' => 'D03', 'distrik_nama' => 'Distrik 3', 'distrik_keterangan' => 'Lamandau'],
            ['distrik_kode' => 'D04', 'distrik_nama' => 'Distrik 4', 'distrik_keterangan' => 'Seruyan, Kotawaringin Timur'],
            ['distrik_kode' => 'D05', 'distrik_nama' => 'Distrik 5', 'distrik_keterangan' => 'Seruyan'],
        ];
    }

    private function kebuns(): array
    {
        // nama, distrik, kabupaten, kelola (kso/mandiri/proses/belum), luas planted Ha
        return [
            ['nama' => 'PT Musirawas Citraharpindo', 'distrik' => 'D01', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 2029.23],
            ['nama' => 'PT Sumur Pandan Wangi', 'distrik' => 'D01', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 1365.83],
            ['nama' => 'PT Gunung Sejahtera Puti Pesona', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'mandiri', 'luas' => 12.72],
            ['nama' => 'PT Natai Sawit Perkasa', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'mandiri', 'luas' => 890.02],
            ['nama' => 'PT Surya Sawit Sejati', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'mandiri', 'luas' => 676.71],
            ['nama' => 'Poktan Sungai Kakap Lestari', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'proses', 'luas' => 476.24],
            ['nama' => 'PT Arut Sawit Mandiri', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 10.48],
            ['nama' => 'PT Menthobi Sawit Jaya', 'distrik' => 'D01', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 7.24],
            ['nama' => 'Koperasi Bedaun Maju Bersama', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 10.84],
            ['nama' => 'PT Agro Menara Rachmat', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 32.13],
            ['nama' => 'PT Citra Borneo Indah', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 2615.88],
            ['nama' => 'PT Gunung Sejahtera Ibu Pertiwi', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 218.86],
            ['nama' => 'PT Kalimantan Sawit Abadi', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 8.47],
            ['nama' => 'PT Mitra Mendawai Sejati', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 45.72],
            ['nama' => 'PT Satya Kisma Usaha', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 875.99],
            ['nama' => 'PT Sawit Sumbermas Sarana', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 79.15],
            ['nama' => 'PT Surya Indah Nusantara Pagi', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 3.23],
            ['nama' => 'PT Bumilanggeng Perdanatrada', 'distrik' => 'D01', 'kab' => 'Kotawaringin Barat', 'kelola' => 'belum', 'luas' => 352.58],
            ['nama' => 'PT Kalimantan Sawit Kusuma', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'kso', 'luas' => 5898.08],
            ['nama' => 'PT Sukses Karya Mandiri', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'kso', 'luas' => 2795.45],
            ['nama' => 'PT Harapan Hibrida Kalbar', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'mandiri', 'luas' => 123.84],
            ['nama' => 'PT Sungai Rangit', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'proses', 'luas' => 2418.29],
            ['nama' => 'PT Usaha Agro Indonesia', 'distrik' => 'D02', 'kab' => 'Kotawaringin Barat', 'kelola' => 'proses', 'luas' => 1165.73],
            ['nama' => 'Koperasi Jorong Rayo', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'belum', 'luas' => 359.58],
            ['nama' => 'KUD Beringin Jaya', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'belum', 'luas' => 1858.68],
            ['nama' => 'PT Graha Cakramulia', 'distrik' => 'D02', 'kab' => 'Sukamara', 'kelola' => 'belum', 'luas' => 29.40],
            ['nama' => 'PT Menthobi Makmur Lestari', 'distrik' => 'D02', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 52.67],
            ['nama' => 'PT Sumber Mahardhika Graha', 'distrik' => 'D02', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 43.11],
            ['nama' => 'PT Putra Pendawa Lima', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'kso', 'luas' => 214.00],
            ['nama' => 'PT Nirmala Agro Lestari', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'mandiri', 'luas' => 42.93],
            ['nama' => 'PT First Lamandau Timber International', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'mandiri', 'luas' => 39.00],
            ['nama' => 'Koperasi Bukit Lumut Sejahtera', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 102.40],
            ['nama' => 'Koperasi Guna Wana Sejahtera B', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 28.93],
            ['nama' => 'Koperasi Tiga Putera Bungou', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 80.99],
            ['nama' => 'PT Ahmad Saleh Perkasa B', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 466.58],
            ['nama' => 'PT Berkat Prima Sejati', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 2500.00],
            ['nama' => 'PT Gema Reksa Mekarsari', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 4.89],
            ['nama' => 'PT Menthobi Mitra Lestari', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 2184.98],
            ['nama' => 'PT Menthobi Mitra Sentosa', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 33.89],
            ['nama' => 'PT Mirza Pratama Putra', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 1057.51],
            ['nama' => 'PT Sawit Lamandau Raya', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'proses', 'luas' => 30.00],
            ['nama' => 'PT Sawit Mandiri Lestari', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'proses', 'luas' => 29.19],
            ['nama' => 'PT Sawit Multi Utama', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 41.65],
            ['nama' => 'PT Tanjung Sawit Abadi', 'distrik' => 'D03', 'kab' => 'Lamandau', 'kelola' => 'belum', 'luas' => 703.47],
            ['nama' => 'PT Agrokarya Prima Lestari', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 2392.51],
            ['nama' => 'PT Karya Makmur Sejahtera', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 2761.54],
            ['nama' => 'PT Aditunggal Mahajaya', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 0.04],
            ['nama' => 'PT Bina Sawit Abadi Pratama 1', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 36.86],
            ['nama' => 'PT Mitrakarya Agroindo', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 94.64],
            ['nama' => 'PT Tapian Nadenggan', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 64.91],
            ['nama' => 'PT Telaga Sari Persada', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 1569.28],
            ['nama' => 'PT Buana Artha Sejahtera', 'distrik' => 'D04', 'kab' => 'Kotawaringin Timur', 'kelola' => 'belum', 'luas' => 1703.00],
            ['nama' => 'PT Ciptatani Kumai Sejahtera', 'distrik' => 'D04', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 941.98],
            ['nama' => 'PT Sarana Titian Permata', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 830.87],
            ['nama' => 'PT Rimba Harapan Sakti', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 154.11],
            ['nama' => 'PT Mustika Sembuluh', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 248.61],
            ['nama' => 'PT Rim Capital', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 374.36],
            ['nama' => 'PT Agro Indomas', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'kso', 'luas' => 413.62],
            ['nama' => 'PT Bina Sawit Abadi Pratama Ex. AMP', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'mandiri', 'luas' => 1.61],
            ['nama' => 'Koperasi Tagani Makmur', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 47.72],
            ['nama' => 'Koperasi Tanjung Berkah Sekumpul', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 131.77],
            ['nama' => 'PT Ahmad Saleh Perkasa A', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 9728.04],
            ['nama' => 'PT Gawi Bahandep Sawit Mekar', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 4.34],
            ['nama' => 'PT Kerry Sawit Indonesia', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 202.03],
            ['nama' => 'PT Mega Ika Khansa', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 24.00],
            ['nama' => 'PT Sawitmas Nugraha Perdana', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 1.72],
            ['nama' => 'PT Solonok Ladang Mas', 'distrik' => 'D05', 'kab' => 'Seruyan', 'kelola' => 'belum', 'luas' => 145.24],
        ];
    }

    private function vendors(): array
    {
        return [
            'PT Berlian Bintara Beton', 'PT Karya Makmur Harapan', 'PT Denafa Borneo Lestari Utama',
            'PT Nusantara Minang Sakato', 'PT Infrastruktur Global Investama', 'PT Indowanarim Harapan',
            'PT Surga Graha Nusantara', 'PT Arta Wijaya Sagamata', 'CV Talawang Nusantara',
            'CV Namora Agro Tech', 'PT Selo Emas Agung Abadi', 'PT Infinity Maritim Indonesia',
            'CV Betuah Sawit Raya', 'Koperasi Natai Tengkawang Sejahtera', 'Koperasi Tilap Mandiri Sejahtera',
            'ABDUL AZIZ', 'AGUS ADI SUSANTO', 'AJA NAKA', 'AJUSTUS KURNIADI', 'BAHARUN', 'CUNALIUS',
            'CV ARDA BESI PERKASA', 'CV KENYALANG JAYA LESTARI', 'CV MOLA JAYA LESTARI', 'CV PIPIN',
            'CV WEN WEN LESTARI', 'CV. ABADI RIZQI', 'CV. ARDA BESI PERKASA', 'CV. DUA PUTRA MANDIRI DESA',
            'CV. MOLA JAYA LESTARI', 'CV. MOLA LESTARI JAYA', 'CV. PIPIN', 'CV. PIPIN GROUP',
            'CV. TRIYASA SUKSES SEJAHTERA', 'CV. WEN WEN LESTARI', 'CV. WEN-WEN LESTARI',
            'DEDI SUMITRO', 'DESI SUMITRO', 'ESTER', 'FRANCISCO GUTERRES', 'FRANSISCO GUTERRES',
            'FRANSISCO GUTTERES', 'GIUS PURNOMO', 'GIYUS PURNOMO', 'GUNAWAN', 'HARDI KARIM', 'HENDRI',
            'HERI BERTUS', 'HISKIA LASNO', 'IRWAN SUHAIRI', 'ISHARYANTI', 'ISHARYATI', 'KAMARUDIN',
            'MINTA WATI', 'MUNAWAR HUSIEN', 'PT ADIGUNA KARYA MAKMUR', 'PT AGRO TUNAS MANDIRI',
            'PT DELTA JAYA', 'PT GEMILANG UTAMA NUSANTARA', 'PT LOWVIGA INTI SEJAHTERA',
            'PT PRIMA SARANA MUSTIKA', 'PT PURNAMA AGUNG SARANA', 'PT PUSAKA AGUNG SARANA',
            'PT. ADIGUNA KARYA', 'PT. ADIGUNA KARYA MAKMUR', 'PT. AGRO TUNAI MANDIRI',
            'PT. ANDALAN MAJU PERKASA', 'PT. GEMILANG UTAMA NUSANTARA', 'PT. JAGAAMAN SARANA',
            'PT. LOWVIGA INTI SEJAHTERA', 'PT. PRIMA SARANA MUSTIKA', 'PT. PURNAMA AGUNG SARANA',
            'SAMIN', 'SAMUEL RAGA', 'SANGGIN', 'SUSANAWATI', 'TANGGI', 'TITIYUS LOIS', 'ZENOBIUS TOBING',
        ];
    }
}
