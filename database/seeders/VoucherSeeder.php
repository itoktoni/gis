<?php

namespace Database\Seeders;

use App\Enums\VoucherStatusEnum;
use App\Models\Kebun;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Models\VoucherHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = Vendor::orderBy('vendor_kode')->limit(6)->get();
        if ($vendors->isEmpty()) {
            return;
        }

        $kebuns = Kebun::orderBy('kebun_id')->limit(4)->pluck('kebun_id')->all();
        $estates = ['Peturau 1', 'Peturau 2', 'KBB', 'CSR'];
        $satuans = ['KG', 'HK', 'Unit', 'Hektar'];
        $flows = [
            [VoucherStatusEnum::DRAFT, []],
            [VoucherStatusEnum::VERIFIKASI_FA, [VoucherStatusEnum::DRAFT, VoucherStatusEnum::VERIFIKASI_FA]],
            [VoucherStatusEnum::VERIFIKASI_AP, [VoucherStatusEnum::DRAFT, VoucherStatusEnum::VERIFIKASI_FA, VoucherStatusEnum::VERIFIKASI_AP]],
            [VoucherStatusEnum::KIRIM_JAKARTA, [VoucherStatusEnum::DRAFT, VoucherStatusEnum::VERIFIKASI_FA, VoucherStatusEnum::VERIFIKASI_AP, VoucherStatusEnum::KIRIM_JAKARTA]],
            [VoucherStatusEnum::SIAP_BAYAR, [VoucherStatusEnum::DRAFT, VoucherStatusEnum::VERIFIKASI_FA, VoucherStatusEnum::VERIFIKASI_AP, VoucherStatusEnum::KIRIM_JAKARTA, VoucherStatusEnum::SIAP_BAYAR]],
            [VoucherStatusEnum::SELESAI, [VoucherStatusEnum::DRAFT, VoucherStatusEnum::VERIFIKASI_FA, VoucherStatusEnum::VERIFIKASI_AP, VoucherStatusEnum::KIRIM_JAKARTA, VoucherStatusEnum::SIAP_BAYAR, VoucherStatusEnum::SELESAI]],
            [VoucherStatusEnum::REVISI, [VoucherStatusEnum::DRAFT, VoucherStatusEnum::VERIFIKASI_FA, VoucherStatusEnum::REVISI]],
        ];

        foreach ($flows as $i => [$final, $steps]) {
            $no = 'DUMMY-VCH-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $vendor = $vendors[$i % $vendors->count()];
            $qty = 100 * ($i + 1) + 50;
            $nilai = $qty * (220 + $i * 15);
            $tanggal = Carbon::today()->subDays(count($flows) - $i)->toDateString();

            $voucher = Voucher::updateOrCreate(
                ['voucher_no' => $no],
                [
                    'voucher_id_vendor' => $vendor->vendor_id,
                    'voucher_id_kebun' => $kebuns[$i % max(count($kebuns), 1)] ?? null,
                    'voucher_estate' => $estates[$i % count($estates)],
                    'voucher_no_invoice' => 'INV-DMY-'.($i + 1),
                    'voucher_redaksi' => 'Dummy pembayaran pekerjaan borongan tahap '.($i + 1),
                    'voucher_qty' => $qty,
                    'voucher_satuan' => $satuans[$i % count($satuans)],
                    'voucher_nilai' => $nilai,
                    'voucher_tanggal' => $tanggal,
                    'voucher_status' => $final,
                    'voucher_tanggal_bayar' => $final === VoucherStatusEnum::SELESAI ? $tanggal : null,
                ]
            );

            if ($voucher->hasHistory()->count() === 0) {
                $prev = null;
                foreach ($steps as $step) {
                    VoucherHistory::create([
                        'history_id_voucher' => $voucher->voucher_id,
                        'history_status_dari' => $prev,
                        'history_status_ke' => $step,
                        'history_catatan' => $step === VoucherStatusEnum::REVISI ? 'Dummy: dokumen kurang lengkap, mohon dilengkapi.' : 'Dummy: lanjut proses.',
                        'history_id_user' => 1,
                    ]);
                    $prev = $step;
                }
            }
        }
    }
}
