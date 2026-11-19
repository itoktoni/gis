<?php

namespace App\Actions;

use App\Concerns\PayloadTrait;
use App\Models\Kebun;
use App\Models\Lhp;
use App\Models\Pt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import LHP dari file excel distrik (tab per PT maupun DB_All).
 *
 * Mapping kolom otomatis berdasarkan keyword header:
 * transaksi → tabel lhp, master (Luas TM, Target, RKAP) → tabel kebun.
 * Idempotent: baris (kebun, tanggal) yang sama di-upsert.
 */
class LhpImportAction
{
    use AsAction, PayloadTrait;

    public function handle(string $path): array
    {
        set_time_limit(0);

        $wb = IOFactory::load($path);

        // Nilai mentah per sheet (tanpa kalkulasi — file referensi punya
        // formula tak valid di sheet rekap). Referensi sel sederhana
        // (=E5, ='Sheet Lain'!C4) di-resolve manual di bawah.
        $raw = [];
        foreach ($wb->getAllSheets() as $sheet) {
            $raw[$sheet->getTitle()] = $sheet->toArray(null, false, false, false);
        }

        // Bila ada sheet DB_All yang valid, pakai itu saja (isinya sudah
        // gabungan semua tab PT — memproses keduanya hanya duplikat).
        $titles = array_keys($raw);
        $dbAll = null;
        foreach ($titles as $t) {
            if (mb_strtolower(trim($t)) === 'db_all') {
                $dbAll = $t;
                break;
            }
        }
        if ($dbAll !== null && $this->findHeaderRow($raw[$dbAll]) !== null) {
            $titles = [$dbAll];
        }
        $summary = [
            'sheets' => [],
            'rows_read' => 0,
            'upserted' => 0,
            'kebun_created' => [],
            'skipped' => [],
        ];

        $kebunMap = $this->kebunMap();
        $ptMap = Pt::pluck('pt_id', 'pt_nama')
            ->mapWithKeys(fn ($id, $nama) => [mb_strtolower(trim($nama)) => $id])
            ->all();

        foreach ($titles as $title) {
            $grid = $raw[$title];
            if (count($grid) < 2) {
                continue;
            }

            $headRow = $this->findHeaderRow($grid);
            if ($headRow === null) {
                continue;
            }

            $cols = $this->mapColumns($grid[$headRow]);
            if ($cols['tanggal'] === null || $cols['kebun'] === null) {
                $summary['sheets'][] = $title.' (dilewati: header tanggal/kebun tidak ditemukan)';

                continue;
            }

            // Mode per-blok: ada kolom Blok/Kode Blok → rincian per blok + total otomatis.
            if ($cols['blok'] !== null) {
                $count = $this->processBlokRows($grid, $headRow, $cols, $summary, $kebunMap);
                $summary['sheets'][] = $title." ({$count} baris blok)";

                continue;
            }

            $count = 0;
            $batch = [];
            foreach (array_slice($grid, $headRow + 1) as $i => $row) {
                $r = $headRow + 1 + $i;
                $tanggal = $this->parseDate($this->cell($raw, $title, $r, $cols['tanggal']));
                $namaKebun = trim((string) ($this->cell($raw, $title, $r, $cols['kebun']) ?? ''));
                if ($tanggal === null || $namaKebun === '') {
                    continue;
                }
                $summary['rows_read']++;

                $key = mb_strtolower($namaKebun);
                if (! isset($kebunMap[$key])) {
                    $kebun = Kebun::create([
                        'kebun_nama' => $namaKebun,
                        'kebun_id_pt' => $ptMap[$key] ?? null,
                        'kebun_status' => 'aktif',
                    ]);
                    $kebunMap[$key] = $kebun->kebun_id;
                    $summary['kebun_created'][] = $namaKebun;
                }
                $kebunId = $kebunMap[$key];
                $count++;

                // Master kebun — hanya timpa bila nilai excel > 0.
                $val = fn (?int $c) => $c === null ? null : $this->cell($raw, $title, $r, $c);
                $master = array_filter([
                    'kebun_luas_ha' => $this->num($val($cols['luas_tm'])),
                    'kebun_target_produksi_kg' => $this->num($val($cols['target'])),
                    'kebun_rkap_kg' => $this->num($val($cols['rkap'])),
                ], fn ($v) => $v !== null && $v > 0);
                if ($master !== []) {
                    Kebun::where('kebun_id', $kebunId)->update($master);
                }

                $hari = trim((string) ($val($cols['hari']) ?? ''));
                if ($hari === '') {
                    $hari = $tanggal->locale('id')->isoFormat('dddd');
                }

                $batch[] = [
                    'lhp_id_kebun' => $kebunId,
                    'lhp_tanggal' => $tanggal->toDateString(),
                    'lhp_hari' => mb_substr($hari, 0, 15),
                    'lhp_luas_panen' => $this->num($val($cols['luas_panen'])),
                    'lhp_tk_hk' => $this->num($val($cols['tk'])),
                    'lhp_janjang_panen' => $this->int($val($cols['jj_panen'])),
                    'lhp_produksi_panen_kg' => $this->num($val($cols['prod_panen'])),
                    'lhp_janjang_kirim' => $this->int($val($cols['jj_kirim'])),
                    'lhp_produksi_kirim_kg' => $this->num($val($cols['prod_kirim'])),
                    'lhp_brondolan_kg' => $this->num($val($cols['brondolan'])),
                    'lhp_up_le10' => $this->num($val($cols['up_10'])),
                    'lhp_up_11_15' => $this->num($val($cols['up_15'])),
                    'lhp_up_gt15' => $this->num($val($cols['up_gt'])),
                    'lhp_hujan_pagi' => $this->num($val($cols['hujan_pagi'])),
                    'lhp_hujan_siang' => $this->num($val($cols['hujan_siang'])),
                    'lhp_hujan_malam' => $this->num($val($cols['hujan_malam'])),
                    'lhp_blok_restan' => ($v = trim((string) ($val($cols['blok_restan']) ?? ''))) !== '' ? mb_substr($v, 0, 255) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= 500) {
                    $summary['upserted'] += $this->flush($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $summary['upserted'] += $this->flush($batch);
            }
            $summary['sheets'][] = $title." ({$count} baris)";
        }

        return $this->payload(TOAST_SUCCESS, $summary);
    }

    /**
     * Ambil nilai sel dengan me-resolve referensi sederhana:
     * =E5 / =$E$5 (sheet sama) dan ='Nama Sheet'!C4 (antar sheet).
     * Formula kompleks (IFERROR, …) menghasilkan null — kolom hitung
     * memang tidak diimport.
     */
    private function cell(array &$raw, string $title, int $r, ?int $c): mixed
    {
        if ($c === null || ! isset($raw[$title][$r][$c])) {
            return null;
        }
        $v = $raw[$title][$r][$c];
        if (! is_string($v) || ! str_starts_with($v, '=')) {
            return $v;
        }

        $seen = [];
        while (is_string($v) && str_starts_with($v, '=')) {
            $key = $title.'!'.$r.'!'.$c;
            if (isset($seen[$key])) {
                return null;
            }
            $seen[$key] = true;

            $target = $title;
            $ref = $v;
            if (preg_match("/^='?([^'!]+)'?!/u", $ref, $m)) {
                $found = null;
                foreach (array_keys($raw) as $t) {
                    if (mb_strtolower($t) === mb_strtolower($m[1])) {
                        $found = $t;
                        break;
                    }
                }
                if ($found === null) {
                    return null;
                }
                $target = $found;
                $ref = '='.substr($ref, strlen($m[0]));
            }
            if (! preg_match('/^=\$?([A-Z]{1,3})\$?(\d+)$/', strtoupper($ref), $m)) {
                return null;
            }
            $c = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($m[1]) - 1;
            $r = (int) $m[2] - 1;
            $title = $target;
            if (! isset($raw[$title][$r][$c])) {
                return null;
            }
            $v = $raw[$title][$r][$c];
        }

        return $v;
    }

    private function flush(array $batch): int
    {
        $update = [
            'lhp_hari', 'lhp_luas_panen', 'lhp_tk_hk', 'lhp_janjang_panen',
            'lhp_produksi_panen_kg', 'lhp_janjang_kirim', 'lhp_produksi_kirim_kg',
            'lhp_brondolan_kg', 'lhp_up_le10', 'lhp_up_11_15', 'lhp_up_gt15',
            'lhp_hujan_pagi', 'lhp_hujan_siang', 'lhp_hujan_malam',
            'lhp_blok_restan', 'updated_at',
        ];
        DB::table('lhp')->upsert($batch, ['lhp_id_kebun', 'lhp_tanggal'], $update);

        return count($batch);
    }

    private function kebunMap(): array
    {
        return Kebun::pluck('kebun_id', 'kebun_nama')
            ->mapWithKeys(fn ($id, $nama) => [mb_strtolower(trim($nama)) => $id])
            ->all();
    }

    /**
     * Import format per-blok: Tanggal | Kebun | Blok | Luas Panen |
     * Janjang Panen | Janjang Kirim | Brondolan Panen | Brondolan Kirim |
     * Restan (Y/T) | TK | Produksi Panen | Produksi Kirim.
     * Dikelompokkan per (kebun, tanggal): header = jumlah rincian.
     * Blok yang belum ada dibuat otomatis. Idempotent per (kebun, tanggal).
     */
    private function processBlokRows(array $grid, int $headRow, array $cols, array &$summary, array &$kebunMap): int
    {
        $val = fn (array $row, ?int $c) => $c === null ? null : ($row[$c] ?? null);
        $groups = [];
        foreach (array_slice($grid, $headRow + 1) as $row) {
            $tanggal = $this->parseDate($val($row, $cols['tanggal']));
            $namaKebun = trim((string) ($val($row, $cols['kebun']) ?? ''));
            $kodeBlok = trim((string) ($val($row, $cols['blok']) ?? ''));
            if ($tanggal === null || $namaKebun === '' || $kodeBlok === '') {
                continue;
            }
            $key = mb_strtolower($namaKebun);
            if (! isset($kebunMap[$key])) {
                $kebun = Kebun::create(['kebun_nama' => $namaKebun, 'kebun_status' => 'aktif']);
                $kebunMap[$key] = $kebun->kebun_id;
                $summary['kebun_created'][] = $namaKebun;
            }
            $groups[$kebunMap[$key].'|'.$tanggal->toDateString()][] = ['tanggal' => $tanggal, 'kebun_id' => $kebunMap[$key], 'row' => $row];
            $summary['rows_read']++;
        }

        $service = app(\App\Services\LhpBlokService::class);
        $count = 0;
        foreach ($groups as $g) {
            $first = $g[0];
            $lhp = Lhp::firstOrCreate(
                ['lhp_id_kebun' => $first['kebun_id'], 'lhp_tanggal' => $first['tanggal']->toDateString()],
                ['lhp_hari' => mb_substr($first['tanggal']->locale('id')->isoFormat('dddd'), 0, 15)]
            );

            $rows = [];
            $sumTk = 0;
            $sumProdPanen = 0;
            $sumProdKirim = 0;
            foreach ($g as $item) {
                $row = $item['row'];
                $blok = \App\Models\Blok::firstOrCreate(
                    ['blok_id_kebun' => $first['kebun_id'], 'blok_kode' => trim((string) ($val($row, $cols['blok']) ?? ''))],
                    ['blok_nama' => 'Blok '.trim((string) ($val($row, $cols['blok']) ?? ''))]
                );
                $rows[$blok->blok_id] = [
                    'luas_panen' => $val($row, $cols['luas_panen'] ?? null),
                    'janjang_panen' => $val($row, $cols['jj_panen'] ?? null),
                    'janjang_kirim' => $val($row, $cols['jj_kirim'] ?? null),
                    'brondolan_panen' => $val($row, $cols['bro_panen'] ?? $cols['brondolan'] ?? null),
                    'brondolan_kirim' => $val($row, $cols['bro_kirim'] ?? null),
                    'restan' => in_array(mb_strtolower(trim((string) ($val($row, $cols['restan'] ?? null) ?? ''))), ['y', 'ya', 'yes', '1', 'true'], true),
                ];
                // TK & produksi dijumlah per grup (header manual, hasil timbang).
                $sumTk += (float) ($val($row, $cols['tk'] ?? null) ?? 0);
                $sumProdPanen += (float) ($val($row, $cols['prod_panen'] ?? null) ?? 0);
                $sumProdKirim += (float) ($val($row, $cols['prod_kirim'] ?? null) ?? 0);
                $count++;
            }
            $lhp->update([
                'lhp_tk_hk' => $sumTk ?: null,
                'lhp_produksi_panen_kg' => $sumProdPanen ?: null,
                'lhp_produksi_kirim_kg' => $sumProdKirim ?: null,
            ]);
            $service->sync($lhp, $rows);
            $summary['upserted']++;
        }

        return $count;
    }

    /** Cari baris header (mengandung "tanggal") dalam 8 baris pertama. */
    private function findHeaderRow(array $grid): ?int
    {
        foreach (array_slice($grid, 0, 8) as $i => $row) {
            foreach ($row as $cell) {
                if (str_contains(mb_strtolower((string) $cell), 'tanggal')) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** Petakan kolom berdasarkan keyword header (tahan terhadap \n dan variasi label). */
    private function mapColumns(array $headerRow): array
    {
        $norm = array_map(fn ($c) => mb_strtolower(preg_replace('/\s+/', ' ', str_replace("\n", ' ', (string) $c))), $headerRow);
        $find = function (callable $match) use ($norm) {
            foreach ($norm as $i => $h) {
                if ($match($h)) {
                    return $i;
                }
            }

            return null;
        };
        $has = fn (string $h, string ...$needles) => array_reduce($needles, fn ($c, $n) => $c && str_contains($h, $n), true);

        return [
            'tanggal' => $find(fn ($h) => $h === 'tanggal' || str_starts_with($h, 'tanggal')),
            'hari' => $find(fn ($h) => $h === 'hari'),
            'kebun' => $find(fn ($h) => $has($h, 'nama kebun')),
            'luas_tm' => $find(fn ($h) => $has($h, 'luas tm')),
            'luas_panen' => $find(fn ($h) => $has($h, 'luas', 'panen') && ! $has($h, 'output')),
            'tk' => $find(fn ($h) => $has($h, 'tk')),
            'jj_panen' => $find(fn ($h) => $has($h, 'janjang', 'panen')),
            'prod_panen' => $find(fn ($h) => $has($h, 'produksi', 'panen')),
            'target' => $find(fn ($h) => $has($h, 'target')),
            'rkap' => $find(fn ($h) => $has($h, 'rkap')),
            'jj_kirim' => $find(fn ($h) => $has($h, 'janjang', 'kirim')),
            'prod_kirim' => $find(fn ($h) => $has($h, 'produksi', 'kirim')),
            'brondolan' => $find(fn ($h) => $has($h, 'brondolan') && ! $has($h, 'kutip')),
            'up_10' => $find(fn ($h) => $has($h, '10 hari') || $has($h, '<= 10')),
            'up_15' => $find(fn ($h) => $has($h, '11 s.d') || $has($h, '11 s,d')),
            'up_gt' => $find(fn ($h) => $has($h, '> 15')),
            'hujan_pagi' => $find(fn ($h) => $has($h, 'pagi')),
            'hujan_siang' => $find(fn ($h) => $has($h, 'siang')),
            'hujan_malam' => $find(fn ($h) => $has($h, 'malam')),
            'blok_restan' => $find(fn ($h) => $has($h, 'blok restan')),
            'blok' => $find(fn ($h) => $has($h, 'blok') && ! $has($h, 'restan')),
            'bro_panen' => $find(fn ($h) => $has($h, 'brondolan') && $has($h, 'panen')),
            'bro_kirim' => $find(fn ($h) => $has($h, 'brondolan') && $has($h, 'kirim')),
            'restan' => $find(fn ($h) => $has($h, 'restan') && ! $has($h, 'blok')),
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        try {
            if ($value === null || $value === '') {
                return null;
            }
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            }
            // Format Indonesia dulu (strtotime mengira m/d/Y untuk pemisah "/").
            foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y'] as $f) {
                try {
                    return Carbon::createFromFormat($f, trim((string) $value))->startOfDay();
                } catch (\Throwable) {
                }
            }

            return Carbon::parse(trim((string) $value))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function num(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $clean = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        return $clean === '' || $clean === '-' ? null : (float) str_replace(',', '.', $clean);
    }

    private function int(mixed $value): ?int
    {
        $n = $this->num($value);

        return $n === null ? null : (int) round($n);
    }
}
