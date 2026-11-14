<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Distrik;
use App\Models\Kebun;
use App\Models\Objek;
use App\Models\Pt;

class KebunController extends Controller
{
    use ControllerTrait;

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'ptOptions' => Pt::getCodeOptions(),
            'distrikOptions' => Distrik::getCodeOptions(),
            'statusOptions' => [
                'aktif' => 'Aktif',
                'nonaktif' => 'Nonaktif',
                'replanting' => 'Replanting',
            ],
            'objekMap' => Objek::select('objek_id', 'objek_nama', 'objek_jenis', 'objek_lat', 'objek_lng')->get(),
        ];

        return array_merge($default, $data);
    }

    public function __construct(Kebun $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'kebun_id_pt' => ['hasPt', ['pt_nama', 'pt_kode']],
            'kebun_id_distrik' => ['hasDistrik', ['distrik_nama', 'distrik_kode']],
        ];
    }

    protected function baseQuery()
    {
        return scopeKebun($this->model->with(['hasPt', 'hasDistrik'])->withCount(['hasMasalah as has_masalah_aktif_count' => fn ($q) => $q->where('masalah_status', '!=', 'selesai')]), 'kebun_id');
    }
}
