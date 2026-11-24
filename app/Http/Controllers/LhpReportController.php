<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Kebun;
use App\Models\Lhp;
use Illuminate\Http\Request;

/**
 * Report LHP — adopsi pola ReportDataLinenController (bac).
 *
 * - Tanpa $this->model sehingga lolos GeneralRequest::authorize()
 *   dan tidak butuh Policy baru.
 * - set_time_limit(0) agar tidak timeout.
 * - getPrint tanpa limit — bila baris melebihi REPORT_CHUNK
 *   otomatis dialihkan ke streaming Excel.
 * - getExportExcel streaming per chunk 1000 + flush (tanpa ->get()),
 *   jadi memory tetap kecil berapa pun jumlah barisnya.
 */
class LhpReportController extends Controller
{
    use ControllerTrait;

    public $model = null;

    public function getTable(GeneralRequest $request)
    {
        // Hanya form filter — hasil dibuka di getPrint (tab baru).
        return $this->views($this->template(), $this->share());
    }

    public function getPrint(Request $request)
    {
        set_time_limit(0);

        $query = $this->baseQuery($request);

        // Render HTML puluhan ribu baris bikin browser/PHP jebol,
        // jadi bila melebihi ambang langsung kirim sebagai streaming Excel.
        $threshold = (int) env('REPORT_CHUNK', 10000);
        if ($threshold > 0 && (clone $query)->count() > $threshold) {
            return $this->getExportExcel($request);
        }

        $data = (clone $query)->orderBy('lhp_tanggal')->orderBy('lhp_id_kebun')->get();

        // Pusingan tiap blok yang dipanen pada tiap baris (sama seperti tabel transaksi).
        $lhpIds = $data->map(fn ($l) => (int) $l->lhp_id)->all();
        $pusinganByLhp = [];
        if ($lhpIds !== []) {
            $rows = \App\Models\LhpBlok::with(['hasLhp', 'hasBlok'])
                ->whereIn('lhp_blok_id_lhp', $lhpIds)->get();
            app(\App\Services\LhpBlokService::class)->attachPusingan($rows);
            foreach ($rows as $r) {
                $pusinganByLhp[(int) $r->lhp_blok_id_lhp][] = [
                    'kode' => $r->hasBlok?->blok_kode ?? '?',
                    'hari' => $r->pusingan_hari,
                    'prev' => $r->pusingan_prev,
                ];
            }
        }

        return $this->views($this->template(), array_merge($this->share(), [
            'data' => $data,
            'opening' => $this->restanOpening($request),
            'pusinganByLhp' => $pusinganByLhp,
        ]));
    }

    /** Filter khusus report per blok (menu terpisah). */
    public function getTableBlok(GeneralRequest $request)
    {
        return $this->views($this->template('table-blok'), $this->share());
    }

    /** Print per blok: 1 baris per blok per tanggal. */
    public function getBlok(Request $request)
    {
        set_time_limit(0);

        $query = $this->baseBlokQuery($request);

        $threshold = (int) env('REPORT_CHUNK', 10000);
        if ($threshold > 0 && (clone $query)->count() > $threshold) {
            return $this->getExportBlok($request);
        }

        $data = (clone $query)->get()->sortBy([
            fn ($r) => $r->hasLhp?->lhp_tanggal,
            fn ($r) => $r->hasLhp?->lhp_id_kebun,
            fn ($r) => $r->hasBlok?->blok_kode,
        ])->values();

        $this->blokSvc()->attachPusingan($data);

        return $this->views($this->template('blok'), array_merge($this->share(), [
            'data' => $data,
        ]));
    }

    /**
     * Export Excel via HTML table (dibuka langsung di Excel) —
     * kolom persis tab PT di file referensi (No s/d Total, 27 kolom),
     * streaming per chunk 1000 + flush, tanpa paket tambahan,
     * tanpa limit — sebesar filter.
     */
    public function getExportExcel(Request $request)
    {
        set_time_limit(0);

        $filename = 'report-lhp-'.now()->format('Ymd-His').'.xls';
        $opening = $this->restanOpening($request);

        return response()->streamDownload(function () use ($request, $opening) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head><body>';
            echo '<table><tr><td colspan="30"><b>LAPORAN HARIAN PRODUKSI (LHP)</b></td></tr>';
            echo '<tr><td colspan="30"><b>PERIODE : '.e($request->input('dari') ?: '-').' s/d '.e($request->input('sampai') ?: '-').'</b></td></tr></table><br>';
            echo '<table border="1"><thead><tr>'
                .'<th>No</th><th>Tanggal</th><th>Hari</th><th>Nama Kebun</th>'
                .'<th>Luas TM<br>Planted (Ha)</th><th>Luas<br>Panen (Ha)</th><th>TK Panen<br>(HK)</th>'
                .'<th>Janjang<br>Panen (Jjg)</th><th>Produksi<br>Panen (Kg)</th>'
                .'<th>Output Panen<br>(Ha/HK)</th><th>Output Panen<br>(Kg/HK)</th>'
                .'<th>Target<br>Produksi (Kg)</th><th>Produksi TBS<br>RKAP (Kg)</th>'
                .'<th>Janjang<br>Kirim (Jjg)</th><th>Produksi TBS<br>Kirim (Kg)</th>'
                .'<th>BJR</th><th>Brondolan<br>(Kg)</th><th>% Kutip<br>Brondolan</th>'
                .'<th>Restan<br>(Kg)</th><th>Restan<br>Jjg</th><th>Restan Brd<br>(Kg)</th>'
                .'<th>UP Ha<br>&lt;= 10 Hari</th><th>UP Ha<br>11 s.d 15 Hari</th><th>UP Ha<br>&gt; 15 Hari</th>'
                .'<th>Pagi<br>Jam 6 - 11</th><th>Siang<br>Jam 11 - 16</th><th>Malam<br>Jam 16 - 6</th><th>Total</th>'
                .'<th>Pusingan Blok</th>'
                .'</tr></thead><tbody>';

            $no = 0;
            $restan = [];
            $pusinganCache = [];
            $blokSvc = app(\App\Services\LhpBlokService::class);
            $this->baseQuery($request)
                ->orderBy('lhp_tanggal')
                ->orderBy('lhp_id_kebun')
                ->chunk(1000, function ($rows) use (&$no, &$restan, $opening, &$pusinganCache, $blokSvc) {
                    // Pusingan blok-blok pada chunk ini (histori global, cache antar-chunk).
                    $blokRows = \App\Models\LhpBlok::with(['hasLhp', 'hasBlok'])
                        ->whereIn('lhp_blok_id_lhp', $rows->map(fn ($t) => (int) $t->lhp_id)->all())->get();
                    $blokSvc->attachPusingan($blokRows, $pusinganCache);
                    $pusMap = [];
                    foreach ($blokRows as $br) {
                        $pusMap[(int) $br->lhp_blok_id_lhp][] = ($br->hasBlok?->blok_kode ?? '?').': '.($br->pusingan_hari === null ? 'perdana' : 'Pusingan '.$br->pusingan_hari.' hr');
                    }
                    foreach ($rows as $table) {
                        $no++;
                        $pusTxt = implode('; ', $pusMap[(int) $table->lhp_id] ?? []);
                        $kid = $table->lhp_id_kebun;
                        $tk = (float) ($table->lhp_tk_hk ?? 0);
                        $luasPanen = (float) ($table->lhp_luas_panen ?? 0);
                        $prodPanen = (float) ($table->lhp_produksi_panen_kg ?? 0);
                        $jjKirim = (int) ($table->lhp_janjang_kirim ?? 0);
                        $prodKirim = (float) ($table->lhp_produksi_kirim_kg ?? 0);
                        $brondolan = (float) ($table->lhp_brondolan_kg ?? 0);
                        if (! array_key_exists($kid, $restan)) {
                            $restan[$kid] = (float) ($opening[$kid] ?? 0);
                        }
                        $restan[$kid] += $prodPanen - $prodKirim;
                        $hujanPagi = (float) ($table->lhp_hujan_pagi ?? 0);
                        $hujanSiang = (float) ($table->lhp_hujan_siang ?? 0);
                        $hujanMalam = (float) ($table->lhp_hujan_malam ?? 0);
                        echo '<tr><td>'.$no.'</td>'
                            .'<td>'.e($table->lhp_tanggal?->format('Y-m-d')).'</td>'
                            .'<td>'.e($table->lhp_hari ?? '-').'</td>'
                            .'<td>'.e($table->hasKebun?->kebun_nama ?? '-').'</td>'
                            .'<td>'.e($table->hasKebun?->kebun_luas_ha ?? 0).'</td>'
                            .'<td>'.e($table->lhp_luas_panen ?? 0).'</td>'
                            .'<td>'.e($table->lhp_tk_hk ?? 0).'</td>'
                            .'<td>'.e($table->lhp_janjang_panen ?? 0).'</td>'
                            .'<td>'.e($table->lhp_produksi_panen_kg ?? 0).'</td>'
                            .'<td>'.($tk > 0 ? round($luasPanen / $tk, 4) : 0).'</td>'
                            .'<td>'.($tk > 0 ? round($prodPanen / $tk, 2) : 0).'</td>'
                            .'<td>'.e($table->hasKebun?->kebun_target_produksi_kg ?? 0).'</td>'
                            .'<td>'.e($table->hasKebun?->kebun_rkap_kg ?? 0).'</td>'
                            .'<td>'.e($table->lhp_janjang_kirim ?? 0).'</td>'
                            .'<td>'.e($table->lhp_produksi_kirim_kg ?? 0).'</td>'
                            .'<td>'.($jjKirim > 0 ? round($prodKirim / $jjKirim, 2) : 0).'</td>'
                            .'<td>'.e($table->lhp_brondolan_kg ?? 0).'</td>'
                            .'<td>'.($prodKirim > 0 ? round($brondolan / $prodKirim * 100, 2) : 0).'</td>'
                            .'<td>'.round($restan[$kid], 2).'</td>'
                            .'<td>'.e($table->lhp_restan_janjang ?? 0).'</td>'
                            .'<td>'.e($table->lhp_restan_brondolan_kg ?? 0).'</td>'
                            .'<td>'.e($table->lhp_up_le10 ?? 0).'</td>'
                            .'<td>'.e($table->lhp_up_11_15 ?? 0).'</td>'
                            .'<td>'.e($table->lhp_up_gt15 ?? 0).'</td>'
                            .'<td>'.e($table->lhp_hujan_pagi ?? 0).'</td>'
                            .'<td>'.e($table->lhp_hujan_siang ?? 0).'</td>'
                            .'<td>'.e($table->lhp_hujan_malam ?? 0).'</td>'
                            .'<td>'.round($hujanPagi + $hujanSiang + $hujanMalam, 2).'</td>'
                            .'<td>'.e($pusTxt !== '' ? $pusTxt : '-').'</td></tr>';
                    }
                    flush();
                });

            echo '</tbody></table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Export Excel per blok via HTML table streaming. */
    public function getExportBlok(Request $request)
    {
        set_time_limit(0);

        $filename = 'report-lhp-blok-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($request) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head><body>';
            echo '<table><tr><td colspan="14"><b>LAPORAN HARIAN PRODUKSI (LHP) PER BLOK</b></td></tr>';
            echo '<tr><td colspan="14"><b>PERIODE : '.e($request->input('dari') ?: '-').' s/d '.e($request->input('sampai') ?: '-').'</b></td></tr></table><br>';
            echo '<table border="1"><thead><tr>'
                .'<th>No.</th><th>Tanggal</th><th>Nama Kebun</th><th>Kode Blok</th><th>Nama Blok</th>'
                .'<th>Luas Panen (Ha)</th>'
                .'<th>Jjg Panen</th><th>Jjg Kirim</th><th>Restan Jjg</th>'
                .'<th>Brd Panen (Kg)</th><th>Brd Kirim (Kg)</th><th>Restan Brd (Kg)</th>'
                .'<th>Restan</th><th>Pusingan</th>'
                .'</tr></thead><tbody>';

            $no = 0;
            $pusinganCache = [];
            $this->baseBlokQuery($request)->chunk(1000, function ($rows) use (&$no, &$pusinganCache) {
                $this->blokSvc()->attachPusingan($rows, $pusinganCache);
                foreach ($rows as $table) {
                    $no++;
                    $pusingan = $table->pusingan_hari === null ? 'perdana' : ('Pusingan '.$table->pusingan_hari.' hari'.($table->pusingan_prev ? ' (terakhir '.$table->pusingan_prev.')' : ''));
                    echo '<tr><td>'.$no.'</td>'
                        .'<td>'.e($table->hasLhp?->lhp_tanggal?->format('Y-m-d')).'</td>'
                        .'<td>'.e($table->hasLhp?->hasKebun?->kebun_nama ?? '-').'</td>'
                        .'<td>'.e($table->hasBlok?->blok_kode ?? '-').'</td>'
                        .'<td>'.e($table->hasBlok?->blok_nama ?? '-').'</td>'
                        .'<td>'.e($table->lhp_blok_luas_panen ?? 0).'</td>'
                        .'<td>'.e($table->lhp_blok_janjang_panen ?? 0).'</td>'
                        .'<td>'.e($table->lhp_blok_janjang_kirim ?? 0).'</td>'
                        .'<td>'.e($table->lhp_blok_restan_janjang ?? 0).'</td>'
                        .'<td>'.e($table->lhp_blok_brondolan_panen ?? 0).'</td>'
                        .'<td>'.e($table->lhp_blok_brondolan_kirim ?? 0).'</td>'
                        .'<td>'.e($table->lhp_blok_restan_brondolan ?? 0).'</td>'
                        .'<td>'.($table->lhp_blok_restan ? 'Ya' : '-').'</td>'
                        .'<td>'.e($pusingan).'</td></tr>';
                }
                flush();
            });

            echo '</tbody></table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function share($data = [])
    {
        return array_merge([
            'kebunOptions' => kebunOptions(),
        ], $data);
    }

    /**
     * Saldo awal Restan (Kg) per kebun = akumulasi (panen - kirim)
     * sebelum tanggal "dari", agar Restan periode berjalan
     * nyambung seperti rumus kumulatif di file excel.
     *
     * @return array<int, float> [lhp_id_kebun => saldo]
     */
    private function restanOpening(Request $request): array
    {
        if (! $request->filled('dari')) {
            return [];
        }

        $query = scopeKebun(Lhp::query(), 'lhp_id_kebun')
            ->whereDate('lhp_tanggal', '<', $request->input('dari'));

        if ($request->filled('kebun_id')) {
            $query->where('lhp_id_kebun', $request->input('kebun_id'));
        }

        return $query
            ->selectRaw('lhp_id_kebun, COALESCE(SUM(lhp_produksi_panen_kg), 0) - COALESCE(SUM(lhp_produksi_kirim_kg), 0) AS saldo')
            ->groupBy('lhp_id_kebun')
            ->pluck('saldo', 'lhp_id_kebun')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function blokSvc(): \App\Services\LhpBlokService
    {
        return app(\App\Services\LhpBlokService::class);
    }

    private function baseQuery(Request $request)
    {
        $query = scopeKebun(Lhp::with(['hasKebun.hasPt']), 'lhp_id_kebun');

        if ($request->filled('dari')) {
            $query->whereDate('lhp_tanggal', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('lhp_tanggal', '<=', $request->input('sampai'));
        }
        if ($request->filled('kebun_id')) {
            $query->where('lhp_id_kebun', $request->input('kebun_id'));
        }

        return $query;
    }

    /** Query rincian per blok dengan filter tanggal + kebun + hak akses yang sama. */
    private function baseBlokQuery(Request $request)
    {
        $query = \App\Models\LhpBlok::with(['hasLhp.hasKebun.hasPt', 'hasBlok'])
            ->whereHas('hasLhp', function ($q) use ($request) {
                if ($request->filled('dari')) {
                    $q->whereDate('lhp_tanggal', '>=', $request->input('dari'));
                }
                if ($request->filled('sampai')) {
                    $q->whereDate('lhp_tanggal', '<=', $request->input('sampai'));
                }
                if ($request->filled('kebun_id')) {
                    $q->where('lhp_id_kebun', $request->input('kebun_id'));
                }
                scopeKebun($q, 'lhp_id_kebun');
            });

        return $query;
    }
}
