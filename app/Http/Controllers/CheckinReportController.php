<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Checkin;
use App\Models\Kunjungan;
use App\Models\User;
use Illuminate\Http\Request;

class CheckinReportController extends Controller
{
    use ControllerTrait {
        getDelete as protected traitGetDelete;
        postDelete as protected traitPostDelete;
    }

    public function __construct(Checkin $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'user_id' => ['hasUser', 'name'],
        ];
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'userOptions' => User::orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => [
                'valid' => 'Valid',
                'invalid' => 'Di Luar Jangkauan',
                'fake' => 'Palsu',
            ],
            'jenisOptions' => [
                'checkin' => 'Checkin',
                'kunjungan' => 'Kunjungan',
            ],
        ];

        return array_merge($default, $data);
    }

    protected function baseQuery()
    {
        $query = $this->model->with(['hasUser', 'hasKunjungan']);

        if (request()->filled('user_id')) {
            $query->where('user_id', request()->input('user_id'));
        }
        if (request()->filled('status')) {
            $query->where('status', request()->input('status'));
        }
        $this->applyJenisFilter($query);
        if (request()->filled('from')) {
            $query->whereDate('created_at', '>=', request()->input('from'));
        }
        if (request()->filled('to')) {
            $query->whereDate('created_at', '<=', request()->input('to'));
        }

        return $query;
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('created_at');
        }
    }

    public function getTable(GeneralRequest $request)
    {
        // Default: hari ini saja bila filter tanggal kosong
        if (! $request->filled('from') && ! $request->filled('to') && ! $request->boolean('all')) {
            $today = now()->toDateString();
            $request->merge(['from' => $today, 'to' => $today]);
        }
        $data = $this->getData()->cursorPaginate($request->input('per_page', 25))->withQueryString();

        return $this->views($this->template(), [
            'data' => $data,
            'fields' => $this->getFields(),
        ]);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        $this->deletePhotos([$id]);

        return $this->traitGetDelete($request, $id);
    }

    public function postDelete(GeneralRequest $request)
    {
        $this->deletePhotos((array) $request->input('ids', []));

        return $this->traitPostDelete($request);
    }

    /** Hapus file foto check-in dari storage agar tidak jadi sampah. */
    private function deletePhotos(array $ids): void
    {
        $ids = array_filter($ids);
        if ($ids === []) {
            return;
        }
        $paths = $this->model->whereIn('id', $ids)->pluck('photo_path')->filter()->unique();
        foreach ($paths as $path) {
            $file = storage_path('app/public/'.$path);
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function getShow(GeneralRequest $request, $id)
    {
        $checkin = $this->model->with(['hasUser', 'hasKunjungan'])->findOrFail($id);
        $kunjungan = Kunjungan::where('kunjungan_id_checkin', $checkin->id)->first();

        return $this->views($this->template(), array_merge($this->share(), [
            'model' => $checkin,
            'kunjungan' => $kunjungan,
        ]));
    }

    public function getSummary(GeneralRequest $request)
    {
        if (! $request->filled('from') && ! $request->filled('to')) {
            $today = now()->toDateString();
            $request->merge(['from' => now()->startOfMonth()->toDateString(), 'to' => $today]);
        }
        $summary = $this->buildSummary($request);

        return $this->views($this->template(), [
            'summary' => $summary,
        ]);
    }

    public function getPrint(GeneralRequest $request)
    {
        set_time_limit(0);
        if (! $request->filled('from') && ! $request->filled('to')) {
            $request->merge(['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]);
        }
        $data = $this->filteredQuery($request)->orderBy('created_at')->get();
        $summary = $this->buildSummary($request);

        return $this->views($this->template(), [
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    public function getExportExcel(GeneralRequest $request)
    {
        set_time_limit(0);
        $filename = 'report-absen-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($request) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head><body>';
            echo '<h3>SUMMARY ABSEN</h3>';
            echo '<table border="1"><thead><tr>'
                .'<th>No.</th><th>Karyawan</th><th>Total Check-In</th><th>Checkin</th><th>Kunjungan</th><th>Valid</th>'
                .'<th>Di Luar Jangkauan</th><th>Palsu</th><th>Hari Hadir</th><th>% Valid</th>'
                .'</tr></thead><tbody>';
            $no = 0;
            foreach ($this->buildSummary($request) as $row) {
                $no++;
                echo '<tr><td>'.$no.'</td>'
                    .'<td>'.e($row['nama']).'</td>'
                    .'<td>'.$row['total'].'</td>'
                    .'<td>'.$row['checkin'].'</td>'
                    .'<td>'.$row['kunjungan'].'</td>'
                    .'<td>'.$row['valid'].'</td>'
                    .'<td>'.$row['invalid'].'</td>'
                    .'<td>'.$row['fake'].'</td>'
                    .'<td>'.$row['hari_hadir'].'</td>'
                    .'<td>'.$row['persen_valid'].'</td></tr>';
            }
            echo '</tbody></table><br><h3>DETAIL ABSEN</h3>';
            echo '<table border="1"><thead><tr>'
                .'<th>No.</th><th>Tanggal</th><th>Karyawan</th><th>Jenis</th><th>Titik Kunjungan</th><th>Status</th>'
                .'<th>Selisih Radius (m)</th><th>Alamat</th>'
                .'</tr></thead><tbody>';
            $no = 0;
            $this->filteredQuery($request)->orderBy('created_at')->chunk(1000, function ($rows) use (&$no) {
                foreach ($rows as $table) {
                    $no++;
                    echo '<tr><td>'.$no.'</td>'
                        .'<td>'.e($table->created_at?->format('Y-m-d H:i')).'</td>'
                        .'<td>'.e($table->hasUser?->name ?? '-').'</td>'
                        .'<td>'.($table->kunjungan_id ? 'Kunjungan' : 'Checkin').'</td>'
                        .'<td>'.e($table->hasKunjungan?->kunjungan_nama ?? '-').'</td>'
                        .'<td>'.e($table->status).'</td>'
                        .'<td>'.e($table->selisih_radius_m ?? '-').'</td>'
                        .'<td>'.e($table->address ?? '-').'</td></tr>';
                }
                flush();
            });
            echo '</tbody></table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Filter jenis: checkin (tanpa titik) vs kunjungan (memakai titik). */
    private function applyJenisFilter($query, $request = null): void
    {
        $jenis = ($request ?? request())->input('jenis');
        if ($jenis === 'checkin') {
            $query->whereNull('kunjungan_id');
        } elseif ($jenis === 'kunjungan') {
            $query->whereNotNull('kunjungan_id');
        }
    }

    private function filteredQuery(GeneralRequest|Request $request)
    {
        $query = $this->model->with(['hasUser', 'hasKunjungan']);
        $this->applyJenisFilter($query, $request);
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        return $query;
    }

    private function buildSummary($request): array
    {
        $query = $this->filteredQuery($request);
        $userIds = (clone $query)->distinct()->pluck('user_id')->filter()->values();
        $users = User::whereIn('id', $userIds)->orderBy('name')->get();
        if ($users->isEmpty() && ! $request->filled('user_id')) {
            $users = User::orderBy('name')->limit(200)->get();
        } elseif ($request->filled('user_id')) {
            $users = User::where('id', $request->input('user_id'))->get();
        }
        $out = [];
        foreach ($users as $user) {
            $q = (clone $query)->where('user_id', $user->id);
            $total = (clone $q)->count();
            if ($total === 0 && ! $request->filled('user_id')) {
                continue;
            }
            $valid = (clone $q)->where('status', 'valid')->count();
            $invalid = (clone $q)->where('status', 'invalid')->count();
            $fake = (clone $q)->where('status', 'fake')->count();
            $nCheckin = (clone $q)->whereNull('kunjungan_id')->count();
            $nKunjungan = (clone $q)->whereNotNull('kunjungan_id')->count();
            $hariHadir = (clone $q)->where('status', 'valid')
                ->distinct()->count(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'));
            $out[] = [
                'user_id' => $user->id,
                'nama' => $user->name,
                'email' => $user->email,
                'total' => $total,
                'checkin' => $nCheckin,
                'kunjungan' => $nKunjungan,
                'valid' => $valid,
                'invalid' => $invalid,
                'fake' => $fake,
                'hari_hadir' => $hariHadir,
                'persen_valid' => $total > 0 ? round($valid / $total * 100, 1) : 0,
            ];
        }

        return $out;
    }
}
