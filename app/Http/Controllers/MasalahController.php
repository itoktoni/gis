<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Kebun;
use App\Models\Masalah;
use Illuminate\Validation\ValidationException;

class MasalahController extends Controller
{
    use ControllerTrait {
        postCreate as protected traitPostCreate;
        postUpdate as protected traitPostUpdate;
    }

    public function __construct(Masalah $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'masalah_id_kebun' => ['hasKebun', 'kebun_nama'],
        ];
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'kebunOptions' => kebunOptions(),
            'statusOptions' => Masalah::statusOptions(),
        ];

        return array_merge($default, $data);
    }

    protected function baseQuery()
    {
        return scopeKebun($this->model->with(['hasKebun.hasPt', 'hasKebun.hasDistrik']), 'masalah_id_kebun');
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('masalah_tanggal')->orderByDesc('masalah_id');
        }
    }

    public function postCreate(GeneralRequest $request)
    {
        if (! $request->filled('masalah_status')) {
            $request->merge(['masalah_status' => 'open']);
        }
        if (! $request->filled('masalah_tanggal')) {
            $request->merge(['masalah_tanggal' => now()->toDateString()]);
        }

        $foto = $this->handleFoto($request, null);
        if ($foto !== null) {
            $request->merge(['masalah_file' => $foto]);
        }

        return $this->traitPostCreate($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $masalah = $this->model->findOrFail($id);
        $existing = $masalah->masalah_file ?? null;

        $foto = $this->handleFoto($request, $existing);
        if ($foto !== $existing) {
            $request->merge(['masalah_file' => $foto]);
        }

        return $this->traitPostUpdate($request, $id);
    }

    private function handleFoto(GeneralRequest $request, ?string $existing): ?string
    {
        if ($request->hasFile('masalah_file')) {
            try {
                $path = uploadFile($request->file('masalah_file'), 'masalah', ['max_size' => 5120]);
                $this->deleteMasalahFile($existing);

                return $path;
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['masalah_file' => $e->getMessage()]);
            }
        }

        if ($request->boolean('remove_masalah_file')) {
            $this->deleteMasalahFile($existing);

            return null;
        }

        return $existing;
    }

    private function deleteMasalahFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $file = storage_path('app/public/'.$path);
        if (is_file($file)) {
            unlink($file);
        }
    }
}
