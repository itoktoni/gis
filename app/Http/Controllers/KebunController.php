<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Kebun;
use App\Models\Objek;

class KebunController extends Controller
{
    use ControllerTrait;

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
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
    }
}
