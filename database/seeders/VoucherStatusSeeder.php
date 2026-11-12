<?php

namespace Database\Seeders;

use App\Models\VoucherStatus;
use Illuminate\Database\Seeder;

class VoucherStatusSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['voucher_status_kode' => 'draft', 'voucher_status_nama' => 'Draf', 'voucher_status_no' => 1, 'voucher_status_keterangan' => 'Dokumen baru diinput'],
            ['voucher_status_kode' => 'verifikasi_fa', 'voucher_status_nama' => 'Verifikasi FA Site', 'voucher_status_no' => 2, 'voucher_status_keterangan' => 'Diperiksa FA di site'],
            ['voucher_status_kode' => 'verifikasi_ap', 'voucher_status_nama' => 'Verifikasi Account Payable', 'voucher_status_no' => 3, 'voucher_status_keterangan' => 'Diperiksa tim AP'],
            ['voucher_status_kode' => 'kirim_jakarta', 'voucher_status_nama' => 'Dalam Pengiriman ke Jakarta', 'voucher_status_no' => 4, 'voucher_status_keterangan' => 'Dokumen dikirim ke HO'],
            ['voucher_status_kode' => 'siap_bayar', 'voucher_status_nama' => 'Siap Bayar (Meja Kasir)', 'voucher_status_no' => 5, 'voucher_status_keterangan' => 'Menunggu pembayaran'],
            ['voucher_status_kode' => 'selesai', 'voucher_status_nama' => 'Selesai (Dibayar)', 'voucher_status_no' => 0, 'voucher_status_keterangan' => 'Finish — tidak ada langkah lagi'],
            ['voucher_status_kode' => 'revisi', 'voucher_status_nama' => 'Revisi (Ditolak)', 'voucher_status_no' => null, 'voucher_status_keterangan' => 'Cabang — kembali ke Verifikasi FA'],
        ];

        foreach ($rows as $row) {
            VoucherStatus::updateOrCreate(['voucher_status_kode' => $row['voucher_status_kode']], $row);
        }
    }
}
