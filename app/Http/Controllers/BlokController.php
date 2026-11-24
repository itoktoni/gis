<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Blok;
use App\Models\Kebun;

class BlokController extends Controller
{
    use ControllerTrait;

    public function __construct(Blok $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'blok_id_kebun' => ['hasKebun', 'kebun_nama'],
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
        return scopeKebun($this->model->with(['hasKebun']), 'blok_id_kebun');
    }

    public function getTable(GeneralRequest $request)
    {
        $data = $this->getData()->cursorPaginate($request->input('per_page', 25))->withQueryString();

        // Panen terakhir per blok + pusingan berjalan (s/d hari ini).
        $ids = $data->getCollection()->map(fn ($b) => (int) $b->blok_id)->all();
        $map = app(\App\Services\LhpBlokService::class)->lastPanenMap($ids);
        $today = now()->toDateString();
        foreach ($data as $b) {
            $tgl = $map[(int) $b->blok_id] ?? null;
            $b->panen_terakhir = $tgl;
            $b->pusingan_hari = $tgl ? (int) \Carbon\Carbon::parse($tgl)->diffInDays($today) : null;
        }

        return $this->views($this->template(), [
            'data' => $data,
            'fields' => $this->getFields(),
        ]);
    }
}
