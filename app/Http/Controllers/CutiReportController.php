<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Cuti;
use App\Models\User;
use Illuminate\Http\Request;

class CutiReportController extends Controller
{
    use ControllerTrait;

    public $model = null;

    public function getTable(GeneralRequest $request)
    {
        return $this->views($this->template(), $this->share());
    }

    public function getPrint(Request $request)
    {
        set_time_limit(0);
        $data = $this->baseQuery($request)->orderBy('cuti_tanggal_mulai')->get();
        $summary = $this->summary($request);

        return $this->views($this->template(), array_merge($this->share(), [
            'data' => $data,
            'summary' => $summary,
        ]));
    }

    public function getExportExcel(Request $request)
    {
        set_time_limit(0);
        $filename = 'report-cuti-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($request) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head><body>';
            echo '<table border="1"><thead><tr>'
                .'<th>No.</th><th>Nama</th><th>Jenis</th><th>Mulai</th><th>Selesai</th>'
                .'<th>Hari</th><th>Status</th><th>Keperluan</th><th>Approver</th>'
                .'</tr></thead><tbody>';

            $no = 0;
            $this->baseQuery($request)->orderBy('cuti_tanggal_mulai')->chunk(1000, function ($rows) use (&$no) {
                foreach ($rows as $table) {
                    $no++;
                    echo '<tr><td>'.$no.'</td>'
                        .'<td>'.e($table->hasUser?->name ?? '-').'</td>'
                        .'<td>'.e($table->cuti_jenis).'</td>'
                        .'<td>'.e($table->cuti_tanggal_mulai?->format('Y-m-d')).'</td>'
                        .'<td>'.e($table->cuti_tanggal_selesai?->format('Y-m-d')).'</td>'
                        .'<td>'.e($table->cuti_jumlah_hari).'</td>'
                        .'<td>'.e($table->cuti_status).'</td>'
                        .'<td>'.e($table->cuti_keperluan).'</td>'
                        .'<td>'.e($table->hasApprover?->name ?? '-').'</td></tr>';
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
            'userOptions' => User::orderBy('name')->pluck('name', 'id')->all(),
        ], $data);
    }

    private function baseQuery(Request $request)
    {
        $query = Cuti::with(['hasUser', 'hasApprover']);

        if ($request->filled('dari')) {
            $query->whereDate('cuti_tanggal_mulai', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('cuti_tanggal_selesai', '<=', $request->input('sampai'));
        }
        if ($request->filled('cuti_id_user')) {
            $query->where('cuti_id_user', $request->input('cuti_id_user'));
        }
        if ($request->filled('cuti_status')) {
            $query->where('cuti_status', $request->input('cuti_status'));
        }
        if ($request->filled('cuti_jenis')) {
            $query->where('cuti_jenis', $request->input('cuti_jenis'));
        }

        return $query;
    }

    // Rekap sisa cuti per user pada periode filter
    private function summary(Request $request): array
    {
        $year = $request->filled('dari') ? (int) substr($request->input('dari'), 0, 4) : (int) now()->format('Y');
        $out = [];
        foreach (User::orderBy('name')->get() as $user) {
            $terpakai = Cuti::where('cuti_id_user', $user->id)
                ->where('cuti_status', 'approved')
                ->where('cuti_jenis', 'tahunan')
                ->whereYear('cuti_tanggal_mulai', $year)
                ->sum('cuti_jumlah_hari');
            $out[] = [
                'nama' => $user->name,
                'kuota' => Cuti::kuota($year),
                'terpakai' => (int) $terpakai,
                'sisa' => max(0, Cuti::kuota($year) - (int) $terpakai),
            ];
        }

        return $out;
    }
}
