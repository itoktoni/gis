<?php

namespace Database\Seeders;

use App\Models\Kebun;
use App\Models\Masalah;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MasalahSeeder extends Seeder
{
    public function run(): void
    {
        $ids = Kebun::pluck('kebun_id', 'kebun_nama');
        $t = Carbon::today()->toDateString();

        $rows = [
            ['kebun' => 'PT Musirawas Citraharpindo', 'status' => 'open',
                'permasalahan' => 'Klaim lahan masyarakat melalui DPD Tarik Borneo Bangkule Rajang',
                'kondisi' => 'Masyarakat menyatakan sebagian area eks PT MCH merupakan tanah hak ulayat',
                'dampak' => 'Potensi gangguan operasional dan legalitas lahan',
                'penyebab' => 'Belum terdapat penyelesaian hukum',
                'rtl' => 'Pengajuan bantuan hukum/legal dari HO APN'],
            ['kebun' => 'PT Sumur Pandan Wangi', 'status' => 'open',
                'permasalahan' => 'Klaim lahan masyarakat Paring Raya dengan tuntutan plasma 20%',
                'kondisi' => 'Terdapat tuntutan plasma masyarakat',
                'dampak' => 'Potensi konflik sosial dan operasional',
                'penyebab' => 'Belum terdapat kesepakatan',
                'rtl' => 'Verifikasi legal dan forum komunikasi masyarakat'],
            ['kebun' => 'PT Agrokarya Prima Lestari', 'status' => 'proses',
                'permasalahan' => 'Pencurian buah sawit oleh oknum masyarakat',
                'kondisi' => 'Aktivitas pencurian masih terjadi',
                'dampak' => 'Kehilangan hasil produksi',
                'penyebab' => 'Pengamanan area belum optimal',
                'rtl' => 'Penambahan personel keamanan dan patroli rutin serta BKO'],
            ['kebun' => 'PT Agrokarya Prima Lestari', 'status' => 'open',
                'permasalahan' => 'Klaim lahan di area eks PT AKPL',
                'kondisi' => 'Terdapat klaim masyarakat terhadap area operasional',
                'dampak' => 'Potensi terganggunya aktivitas kebun',
                'penyebab' => 'Data legal dan SHP enclave belum lengkap',
                'rtl' => 'Koordinasi dengan pihak terkait dan inventarisasi data'],
            ['kebun' => 'PT Agrokarya Prima Lestari', 'status' => 'open',
                'permasalahan' => 'Belum lengkapnya personil kebun',
                'kondisi' => 'Terdapat posisi yang masih kosong berdasarkan struktur organisasi kebun',
                'dampak' => 'Belum optimalnya pengelolaan kebun',
                'penyebab' => 'Terkendalanya proses rekrutmen karena perubahan SO HO APN',
                'rtl' => 'Follow up ke tim terkait di HO APN'],
            ['kebun' => 'PT Agrokarya Prima Lestari', 'status' => 'selesai',
                'permasalahan' => 'Belum adanya kendaraan operasional',
                'kondisi' => 'Kendaraan saat ini masih sistem sewa per bulan dan tidak adanya kendaraan patroli',
                'dampak' => 'Terkendalanya patroli, pengawasan kebun, serta potensi pencurian di malam hari',
                'penyebab' => 'Belum turunnya dukungan operasional dari HO',
                'rtl' => 'Follow up ke tim terkait di HO APN'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'open',
                'permasalahan' => 'Klaim lahan masyarakat Desa Kartamulia',
                'kondisi' => 'Terdapat klaim terhadap areal PT Sungai Rangit',
                'dampak' => 'Potensi gangguan keamanan operasional',
                'penyebab' => 'Belum ada penyelesaian mediasi',
                'rtl' => 'Pembentukan tim verifikasi dan mediasi lanjutan'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'proses',
                'permasalahan' => 'Kondisi jalan rusak',
                'kondisi' => 'Akses pengiriman ke POM terganggu',
                'dampak' => 'Keterlambatan pengiriman TBS',
                'penyebab' => 'Perawatan infrastruktur belum optimal',
                'rtl' => 'Perbaikan jalan secara rutin'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'open',
                'permasalahan' => 'Kerusakan unit angkut TBS',
                'kondisi' => 'Beberapa unit mengalami kerusakan',
                'dampak' => 'Distribusi TBS tidak optimal',
                'penyebab' => 'Usia unit dan perawatan',
                'rtl' => 'Perbaikan rutin dan penggantian unit'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'open',
                'permasalahan' => 'Sistem panen kurang efektif',
                'kondisi' => 'TPH dan panen dilakukan bersamaan',
                'dampak' => 'TBS tidak dapat diangkut sekaligus',
                'penyebab' => 'Sistem kerja panen belum optimal',
                'rtl' => 'Perbaikan sistem panen dan alur kerja'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'open',
                'permasalahan' => 'Jarak POM cukup jauh (sekitar 75 km dari area panen)',
                'kondisi' => 'Pengiriman TBS terbatas',
                'dampak' => 'Pengiriman TBS terbatas',
                'penyebab' => 'Kapasitas armada terbatas',
                'rtl' => 'Penambahan unit angkut'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'open',
                'permasalahan' => 'Klaim lahan dan aktivitas panen masyarakat',
                'kondisi' => 'Masyarakat melakukan pematokan dan panen mandiri',
                'dampak' => 'Potensi konflik sosial, operasional, dan kehilangan produksi',
                'penyebab' => 'Area klaim belum terselesaikan',
                'rtl' => 'Patroli rutin dan penambahan personel pengamanan'],
            ['kebun' => 'PT Sungai Rangit', 'status' => 'proses',
                'permasalahan' => 'Pencurian buah sawit dan pondok pemanen',
                'kondisi' => 'Terdapat pondok pemanen di area eks PT KMS',
                'dampak' => 'Potensi kehilangan hasil panen',
                'penyebab' => 'Pengawasan area terbatas',
                'rtl' => 'Pembongkaran pondok dan patroli rutin'],
        ];

        foreach ($rows as $row) {
            if (! isset($ids[$row['kebun']])) {
                continue;
            }
            Masalah::updateOrCreate(
                ['masalah_id_kebun' => $ids[$row['kebun']], 'masalah_permasalahan' => $row['permasalahan']],
                [
                    'masalah_tanggal' => $t,
                    'masalah_kondisi' => $row['kondisi'],
                    'masalah_dampak' => $row['dampak'],
                    'masalah_penyebab' => $row['penyebab'],
                    'masalah_rtl' => $row['rtl'],
                    'masalah_status' => $row['status'],
                ]
            );
        }
    }
}
