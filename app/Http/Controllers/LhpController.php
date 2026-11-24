<?php

namespace App\Http\Controllers;

use App\Actions\LhpImportAction;
use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Lhp;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class LhpController extends Controller
{
    use ControllerTrait {
        postCreate as protected traitPostCreate;
        postUpdate as protected traitPostUpdate;
    }

    public function __construct(Lhp $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'lhp_id_kebun' => ['hasKebun', 'kebun_nama'],
        ];
    }

    protected function share($data = [])
    {
        // Peta kebun → daftar blok (untuk rincian panen per blok di form).
        $blokByKebun = \App\Models\Blok::orderBy('blok_kode')->get()
            ->groupBy('blok_id_kebun')
            ->map(fn ($rows) => $rows->map(fn ($b) => [
                'id' => $b->blok_id,
                'kode' => $b->blok_kode,
                'nama' => $b->blok_nama,
                'luas' => $b->blok_luas_ha,
            ])->values())
            ->all();

        $default = [
            'model' => $this->model,
            'kebunOptions' => kebunOptions(),
            'blokByKebun' => $blokByKebun,
        ];

        return array_merge($default, $data);
    }

    public function getTable(GeneralRequest $request)
    {
        $data = $this->getData()->cursorPaginate($request->input('per_page', 25))->withQueryString();

        // Pusingan tiap blok yang dipanen pada tiap transaksi tampil.
        $lhpIds = $data->getCollection()->map(fn ($l) => (int) $l->lhp_id)->all();
        $pusinganByLhp = [];
        if ($lhpIds !== []) {
            $rows = \App\Models\LhpBlok::with(['hasLhp', 'hasBlok'])
                ->whereIn('lhp_blok_id_lhp', $lhpIds)->get();
            app(\App\Services\LhpBlokService::class)->attachPusingan($rows);
            foreach ($rows as $r) {
                $pusinganByLhp[(int) $r->lhp_blok_id_lhp][] = [
                    'kode' => $r->hasBlok?->blok_kode ?? '?',
                    'hari' => $r->pusingan_hari,
                ];
            }
        }

        return $this->views($this->template(), [
            'data' => $data,
            'fields' => $this->getFields(),
            'pusinganByLhp' => $pusinganByLhp,
        ]);
    }

    protected function baseQuery()
    {
        return scopeKebun($this->model->with(['hasKebun.hasPt']), 'lhp_id_kebun');
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('lhp_tanggal')->orderBy('lhp_id_kebun');
        }
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->mergeDefaults($request);

        $payload = \App\Actions\CreateAction::run($request, $this->model);
        if (! empty($payload['status']) && ! empty($payload['data'])) {
            $this->syncBlokRows($payload['data'], $request);
        }

        return $this->response($payload);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->mergeDefaults($request);

        $payload = \App\Actions\UpdateAction::run($request, $id, $this->model);
        if (! empty($payload['status']) && ! empty($payload['data'])) {
            $this->syncBlokRows($payload['data'], $request);
        }

        return $this->response($payload);
    }

    /**
     * Sinkron rincian panen per blok dari form, lalu hitung ulang total header.
     * Produksi Kg & TK tetap input manual (hasil timbang).
     */
    private function syncBlokRows(Lhp $lhp, GeneralRequest $request): void
    {
        $rows = $request->input('blok', []);
        if (! is_array($rows) || ! $request->exists('blok')) {
            return;
        }

        $mapped = [];
        foreach ($rows as $blokId => $r) {
            if (! is_array($r)) {
                continue;
            }
            $mapped[(int) $blokId] = [
                'luas_panen' => $r['luas_panen'] ?? null,
                'janjang_panen' => $r['janjang_panen'] ?? null,
                'janjang_kirim' => $r['janjang_kirim'] ?? null,
                'brondolan_panen' => $r['brondolan_panen'] ?? null,
                'brondolan_kirim' => $r['brondolan_kirim'] ?? null,
                'restan' => ! empty($r['restan']),
            ];
        }

        app(\App\Services\LhpBlokService::class)->sync($lhp, $mapped);
    }

    public function getImport(GeneralRequest $request)
    {
        return $this->views($this->template('import'), []);
    }

    public function postImport(GeneralRequest $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv|max:20480']);

        $summary = LhpImportAction::run($request->file('file')->getRealPath())['data'];

        return $this->views($this->template('import'), ['summary' => $summary]);
    }

    public function getExportExcel(GeneralRequest $request)
    {
        $tanggal = $request->input('tanggal');
        $isAll = $request->boolean('all');

        $query = $this->model->with(['hasKebun.hasPt'])->orderBy('lhp_tanggal')->orderBy('lhp_id_kebun');

        if (! $isAll && filled($tanggal)) {
            $query->whereDate('lhp_tanggal', $tanggal);
        }

        $data = $query->get();
        $filename = $isAll || blank($tanggal)
            ? 'LHP-ALL-'.now()->format('Ymd').'.xls'
            : 'LHP-'.$tanggal.'.xls';

        return response()
            ->view('pages.lhp.excel', ['data' => $data, 'tanggal' => $tanggal, 'isAll' => $isAll])
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function mergeDefaults(GeneralRequest $request): void
    {
        // Hari dihitung otomatis dari tanggal (data turunan transaksi, bukan master)
        if ($request->filled('lhp_tanggal')) {
            try {
                $hari = Carbon::parse($request->input('lhp_tanggal'))->locale('id')->isoFormat('dddd');
                $request->merge(['lhp_hari' => $hari]);
            } catch (\Throwable) {
            }
        }
    }
}
