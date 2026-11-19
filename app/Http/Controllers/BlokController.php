<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
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
}
