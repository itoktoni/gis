<?php

namespace App\Console\Commands;

use App\Models\Checkin;
use App\Models\User;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAbsenReminder extends Command
{
    protected $signature = 'absen:remind-whatsapp
        {--tanggal= : Tanggal rekap (Y-m-d), default hari ini}
        {--dry-run : Tampilkan pesan tanpa mengirim}';

    protected $description = 'Kirim daftar user yang belum absen (check-in) hari ini ke grup WhatsApp via Fonnte';

    public function handle(FonnteService $fonnte): int
    {
        $tanggal = $this->option('tanggal') ?: Carbon::today('Asia/Jakarta')->toDateString();

        try {
            $tanggal = Carbon::parse($tanggal)->toDateString();
        } catch (\Throwable) {
            $this->error('Format tanggal tidak valid.');

            return self::FAILURE;
        }

        // Rentang hari dalam WIB dikonversi ke UTC: whereDate() memakai waktu DB
        // (umumnya UTC) sehingga check-in pagi WIB tidak jatuh ke "kemarin".
        $dayStartUtc = Carbon::parse($tanggal, 'Asia/Jakarta')->startOfDay()->utc();
        $dayEndUtc = Carbon::parse($tanggal, 'Asia/Jakarta')->endOfDay()->utc();

        $label = Carbon::parse($tanggal)->locale('id')->isoFormat('dddd, D MMMM Y');
        // Kecualikan admin & developer dari rekap.
        $all = User::whereNotIn('role', ['admin', 'developer'])->orderBy('name')->get(['id', 'name']);
        // Sudah absen = ada check-in apa pun hari itu (tidak peduli valid/tidak).
        $reported = Checkin::whereBetween('created_at', [$dayStartUtc, $dayEndUtc])
            ->distinct()
            ->pluck('user_id');
        $missing = $all->whereNotIn('id', $reported)->values();

        $lines = [
            "*Rekap Absen — {$label}*",
            "Sudah absen: {$reported->count()}/{$all->count()} user",
        ];

        if ($reported->isNotEmpty()) {
            // Rincian yang sudah absen + total check-innya hari itu.
            $doneTotals = Checkin::whereBetween('created_at', [$dayStartUtc, $dayEndUtc])
                ->selectRaw('user_id, COUNT(*) as total')
                ->whereIn('user_id', $reported)
                ->groupBy('user_id')
                ->pluck('total', 'user_id');
            $doneUsers = $all->whereIn('id', $reported)->values();
            $lines[] = '';
            $lines[] = '✅ *Sudah absen:*';
            foreach ($doneUsers as $i => $u) {
                $lines[] = ($i + 1).'. '.$u->name.' ('.($doneTotals[$u->id] ?? 0).')';
            }
        }
        $lines[] = '';

        if ($missing->isEmpty()) {
            $lines[] = '✅ Semua user sudah absen. Terima kasih.';
        } else {
            $lines[] = '⚠️ *Belum absen ('.$missing->count().' user):*';
            // Total check-in hari itu per user (semua status) — info tambahan di samping nama.
            $totals = Checkin::whereBetween('created_at', [$dayStartUtc, $dayEndUtc])
                ->selectRaw('user_id, COUNT(*) as total')
                ->whereIn('user_id', $missing->pluck('id'))
                ->groupBy('user_id')
                ->pluck('total', 'user_id');
            $max = 40;
            foreach ($missing->take($max) as $i => $u) {
                $lines[] = ($i + 1).'. '.$u->name.' ('.($totals[$u->id] ?? 0).')';
            }
            if ($missing->count() > $max) {
                $lines[] = '… + '.($missing->count() - $max).' user lainnya (lihat /checkin-report/table)';
            }
            $lines[] = '';
            $lines[] = 'Mohon segera check-in di aplikasi.';
        }

        $message = implode("\n", $lines);

        if ($this->option('dry-run')) {
            $this->line($message);
            $this->line('');
            $this->line('--- debug ---');
            $this->line('rentang WIB: '.$tanggal.' 00:00:00 s/d 23:59:59');
            $this->line('rentang UTC dipakai query: '.$dayStartUtc->toDateTimeString().' s/d '.$dayEndUtc->toDateTimeString());
            $this->line('total baris checkin: '.Checkin::count());
            $this->line('baris dalam rentang: '.Checkin::whereBetween('created_at', [$dayStartUtc, $dayEndUtc])->count());
            $dates = Checkin::selectRaw('DATE(created_at) tgl, COUNT(*) jml')->groupBy('tgl')->orderByDesc('tgl')->take(5)->get();
            foreach ($dates as $d) {
                $this->line('ada data tanggal (DB): '.$d->tgl.' = '.$d->jml.' baris');
            }

            return self::SUCCESS;
        }

        try {
            $result = $fonnte->sendToAbsenGroup($message);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Terkirim ke grup (request id: '.($result['requestid'] ?? '-').').');

        return self::SUCCESS;
    }
}
