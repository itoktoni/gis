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

        return $this->views($this->template(), array_merge($this->share(), [
            'data' => $data,
        ]));
    }

    /**
     * Export Excel via HTML table (dibuka langsung di Excel) —
     * streaming per chunk 1000 + flush, tanpa paket tambahan,
     * tanpa limit — sebesar filter.
     */
    public function getExportExcel(Request $request)
    {
        set_time_limit(0);

        $filename = 'report-lhp-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($request) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head><body>';
            echo '<table><tr><td colspan="16"><b>LAPORAN HARIAN PRODUKSI (LHP)</b></td></tr>';
            echo '<tr><td colspan="16"><b>PERIODE : '.e($request->input('dari') ?: '-').' s/d '.e($request->input('sampai') ?: '-').'</b></td></tr></table><br>';
            echo '<table border="1"><thead><tr>'
                .'<th>No.</th><th>TANGGAL</th><th>HARI</th><th>KEBUN</th><th>PT</th>'
                .'<th>LUAS TM (HA)</th><th>LUAS PANEN (HA)</th><th>TK PANEN (HK)</th>'
                .'<th>JANJANG PANEN</th><th>PRODUKSI PANEN (KG)</th><th>BJR PANEN</th>'
                .'<th>JANJANG KIRIM</th><th>PRODUKSI KIRIM (KG)</th><th>BJR KIRIM</th>'
                .'</tr></thead><tbody>';

            $no = 0;
            $this->baseQuery($request)
                ->orderBy('lhp_tanggal')
                ->orderBy('lhp_id_kebun')
                ->chunk(1000, function ($rows) use (&$no) {
                    foreach ($rows as $table) {
                        $no++;
                        $tk = (float) ($table->lhp_tk_hk ?? 0);
                        $jjPanen = (int) ($table->lhp_janjang_panen ?? 0);
                        $jjKirim = (int) ($table->lhp_janjang_kirim ?? 0);
                        echo '<tr><td>'.$no.'</td>'
                            .'<td>'.e($table->lhp_tanggal?->format('Y-m-d')).'</td>'
                            .'<td>'.e($table->lhp_hari ?? '-').'</td>'
                            .'<td>'.e($table->hasKebun?->kebun_nama ?? '-').'</td>'
                            .'<td>'.e($table->hasKebun?->hasPt?->pt_nama ?? '-').'</td>'
                            .'<td>'.e($table->lhp_luas_tm ?? 0).'</td>'
                            .'<td>'.e($table->lhp_luas_panen ?? 0).'</td>'
                            .'<td>'.e($table->lhp_tk_hk ?? 0).'</td>'
                            .'<td>'.e($table->lhp_janjang_panen ?? 0).'</td>'
                            .'<td>'.e($table->lhp_produksi_panen_kg ?? 0).'</td>'
                            .'<td>'.($jjPanen > 0 ? round((float) $table->lhp_produksi_panen_kg / $jjPanen, 2) : 0).'</td>'
                            .'<td>'.e($table->lhp_janjang_kirim ?? 0).'</td>'
                            .'<td>'.e($table->lhp_produksi_kirim_kg ?? 0).'</td>'
                            .'<td>'.($jjKirim > 0 ? round((float) $table->lhp_produksi_kirim_kg / $jjKirim, 2) : 0).'</td></tr>';
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
}
