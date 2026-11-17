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
        $default = [
            'model' => $this->model,
            'kebunOptions' => kebunOptions(),
        ];

        return array_merge($default, $data);
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

        return $this->traitPostCreate($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->mergeDefaults($request);

        return $this->traitPostUpdate($request, $id);
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
