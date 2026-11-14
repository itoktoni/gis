<?php

namespace App\Services;

use App\Models\Surat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SuratNomorService
{
    private const ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public function placeholders(?string $body): array
    {
        if (blank($body)) {
            return [];
        }

        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $body, $m);

        return array_values(array_unique($m[1] ?? []));
    }

    public function render(?string $body, array $data = []): string
    {
        $out = (string) $body;
        foreach ($data as $key => $value) {
            $out = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string) ($value ?? ''), $out);
        }

        return $out;
    }

    public function nextUrut(string $kodeJenis, string $kodeDivisi, string $tanggal): int
    {
        $tahun = Carbon::parse($tanggal)->format('Y');

        $max = Surat::where('surat_urut', '>', 0)
            ->where('surat_kode_jenis', $kodeJenis)
            ->whereYear('surat_tanggal', $tahun)
            ->whereHas('hasDivisi', fn ($q) => $q->where('divisi_kode', $kodeDivisi))
            ->max('surat_urut');

        return ((int) $max) + 1;
    }

    public function generate(string $jenis, ?string $kodeTemplate, ?string $kodeDivisi, string $tanggal): array
    {
        $kodeJenis = $jenis === 'MASUK' ? 'SM' : ($kodeTemplate ?: 'SK');
        $kodeDivisi = $kodeDivisi ?: 'UMUM';
        $date = Carbon::parse($tanggal);

        return DB::transaction(function () use ($kodeJenis, $kodeDivisi, $date) {
            $urut = $this->nextUrut($kodeJenis, $kodeDivisi, $date->toDateString());
            $nomor = sprintf('%03d', $urut).'/'.$kodeJenis.'/'.$kodeDivisi.'/'.self::ROMAWI[(int) $date->format('n')].'/'.$date->format('Y');

            return ['nomor' => $nomor, 'urut' => $urut];
        });
    }
}
