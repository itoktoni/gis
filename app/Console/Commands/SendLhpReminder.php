<?php

namespace App\Console\Commands;

use App\Models\Kebun;
use App\Models\Lhp;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLhpReminder extends Command
{
    protected $signature = 'lhp:remind-whatsapp
        {--tanggal= : Tanggal rekap (Y-m-d), default hari ini}
        {--dry-run : Tampilkan pesan tanpa mengirim}';

    protected $description = 'Kirim summary LHP harian + daftar kebun belum input ke grup WhatsApp via Fonnte';

    public function handle(FonnteService $fonnte): int
    {
        $tanggal = $this->option('tanggal') ?: Carbon::today()->toDateString();

        try {
            $tanggal = Carbon::parse($tanggal)->toDateString();
        } catch (\Throwable) {
            $this->error('Format tanggal tidak valid.');

            return self::FAILURE;
        }

        $label = Carbon::parse($tanggal)->locale('id')->isoFormat('dddd, D MMMM Y');
        $all = Kebun::orderBy('kebun_nama')->get(['kebun_id', 'kebun_nama']);
        $reported = Lhp::whereDate('lhp_tanggal', $tanggal)->pluck('lhp_id_kebun');
        $missing = $all->whereNotIn('kebun_id', $reported);

        $lines = [
            "*Reminder LHP Harian — {$label}*",
            "Sudah input: {$reported->count()}/{$all->count()} kebun",
            '',
        ];

        if ($missing->isEmpty()) {
            $lines[] = '✅ Semua kebun sudah input. Terima kasih.';
        } else {
            $lines[] = '⚠️ *Belum input ('.$missing->count().' kebun):*';
            $names = $missing->values();
            $max = 40;
            foreach ($names->take($max) as $i => $k) {
                $lines[] = ($i + 1).'. '.$k->kebun_nama;
            }
            if ($names->count() > $max) {
                $lines[] = '… + '.($names->count() - $max).' kebun lainnya (lihat /lhp/table)';
            }
            $lines[] = '';
            $lines[] = 'Mohon segera input di aplikasi sebelum batas waktu.';
        }

        $message = implode("\n", $lines);

        if ($this->option('dry-run')) {
            $this->line($message);

            return self::SUCCESS;
        }

        try {
            $result = $fonnte->sendToLhpGroup($message);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Terkirim ke grup (request id: '.($result['requestid'] ?? '-').').');

        return self::SUCCESS;
    }
}
