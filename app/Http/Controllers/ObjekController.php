<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Kebun;
use App\Models\Objek;

class ObjekController extends Controller
{
    use ControllerTrait;

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'kebunOptions' => Kebun::getOptions(),
            'kebunGeo' => Kebun::select('kebun_id as id', 'kebun_lat as lat', 'kebun_lng as lng')->get()->keyBy('id'),
            'jenisOptions' => [
                'pos_pantau' => 'POS Pemantauan',
                'gudang' => 'Gudang',
                'pintu_masuk' => 'Pintu Masuk',
                'jembatan' => 'Jembatan',
                'kantor' => 'Kantor Lapangan',
                'lainnya' => 'Lainnya',
            ],
        ];

        return array_merge($default, $data);
    }

    public function __construct(Objek $model)
    {
        $this->model = $model::getModel();
    }
}
