<?php

namespace App\Http\Controllers;

use App\Actions\CreateAction;
use App\Actions\UpdateAction;
use App\Concerns\ControllerTrait;
use App\Enums\CutiJenisEnum;
use App\Enums\CutiStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Cuti;
use App\Models\User;
use App\Services\CutiExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CutiController extends Controller
{
    use ControllerTrait;

    public function __construct(Cuti $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'jenisOptions' => CutiJenisEnum::getOptions(),
            'statusOptions' => CutiStatusEnum::getOptions(),
            'userOptions' => User::orderBy('name')->pluck('name', 'id')->all(),
        ];

        return array_merge($default, $data);
    }

    protected function getData()
    {
        $query = $this->model->with(['hasUser', 'hasApprover']);
        // Karyawan biasa hanya melihat pengajuannya sendiri
        if (! in_array(auth()->user()->role ?? '', ['admin', 'developer'])) {
            $query->where('cuti_id_user', auth()->id());
        }

        return $this->applyFilters($query);
    }

    protected function hitungHari(string $mulai, string $selesai): int
    {
        return (int) Carbon::parse($mulai)->diffInDays(Carbon::parse($selesai)) + 1;
    }

    public function postCreate(GeneralRequest $request)
    {
        $request->merge([
            'cuti_id_user' => $request->input('cuti_id_user') ?: auth()->id(),
            'cuti_status' => 'pending',
            'cuti_jumlah_hari' => $this->hitungHari($request->input('cuti_tanggal_mulai'), $request->input('cuti_tanggal_selesai')),
        ]);

        $file = $this->handleFile($request, null);
        if ($file !== null) {
            $request->merge(['cuti_file' => $file]);
        }

        $response = CreateAction::run($request, $this->model);

        return $this->response($response);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $cuti = $this->model->findOrFail($id);
        $request->merge([
            'cuti_jumlah_hari' => $this->hitungHari(
                $request->input('cuti_tanggal_mulai', $cuti->cuti_tanggal_mulai),
                $request->input('cuti_tanggal_selesai', $cuti->cuti_tanggal_selesai)
            ),
        ]);

        $file = $this->handleFile($request, $cuti->cuti_file ?? null);
        if ($file !== $cuti->cuti_file) {
            $request->merge(['cuti_file' => $file]);
        }

        $response = UpdateAction::run($request, $id, $this->model);

        return $this->response($response);
    }

    private function handleFile(GeneralRequest $request, ?string $existing): ?string
    {
        if ($request->hasFile('cuti_file')) {
            try {
                $path = uploadFile($request->file('cuti_file'), 'cuti', ['max_size' => 2048]);
                if (! empty($existing) && file_exists(storage_path('app/public/'.$existing))) {
                    unlink(storage_path('app/public/'.$existing));
                }

                return $path;
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['cuti_file' => $e->getMessage()]);
            }
        }

        if ($request->boolean('remove_cuti_file')) {
            if (! empty($existing) && file_exists(storage_path('app/public/'.$existing))) {
                unlink(storage_path('app/public/'.$existing));
            }

            return null;
        }

        return $existing;
    }

    // Form approve (getApprove) + simpan (postApprove)
    public function getApprove(GeneralRequest $request, $id)
    {
        $data = $this->model->with(['hasUser'])->findOrFail($id);

        return $this->views($this->template('approve'), [
            'model' => $data,
            'sisa' => Cuti::sisa($data->cuti_id_user),
        ]);
    }

    public function postApprove(Request $request, $id)
    {
        $request->validate([
            'cuti_status' => 'required|in:approved,rejected',
            'cuti_catatan' => 'nullable|string',
        ]);

        $cuti = $this->model->findOrFail($id);
        $cuti->update([
            'cuti_status' => $request->input('cuti_status'),
            'cuti_catatan' => $request->input('cuti_catatan'),
            'cuti_id_approver' => auth()->id(),
            'cuti_tanggal_approve' => now(),
        ]);

        return $this->response($this->payload(TOAST_SUCCESS, $cuti));
    }

    // Sisa cuti (JSON + tampil di table via share)
    public function getSisa(Request $request, $userId = null)
    {
        $userId = $userId ?: auth()->id();
        $year = (int) ($request->input('tahun') ?? now()->format('Y'));
        $user = User::findOrFail($userId);

        $data = [
            'user' => $user->name,
            'tahun' => $year,
            'kuota' => Cuti::kuota($year),
            'sisa' => Cuti::sisa((int) $userId, $year),
        ];

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($data);
        }

        return $this->views($this->template('sisa'), array_merge($this->share(), $data));
    }

    // Download formulir permohonan cuti (PDF + DOCX)
    public function getDownload(GeneralRequest $request, $id, CutiExportService $export)
    {
        $cuti = $this->model->with(['hasUser', 'hasApprover'])->findOrFail($id);

        return $export->pdf($cuti)->download('cuti-'.$cuti->cuti_id.'.pdf');
    }

    public function getDownloadDocx(GeneralRequest $request, $id, CutiExportService $export)
    {
        $cuti = $this->model->with(['hasUser', 'hasApprover'])->findOrFail($id);
        $path = $export->docx($cuti);

        return response()->download($path, 'cuti-'.$cuti->cuti_id.'.docx')->deleteFileAfterSend(true);
    }
}
