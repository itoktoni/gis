<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Vendor;

class VendorController extends Controller
{
    use ControllerTrait;

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'jenisOptions' => [
                'perorangan' => 'Perorangan',
                'cv' => 'CV',
                'pt' => 'PT',
                'koperasi' => 'Koperasi',
            ],
        ];

        return array_merge($default, $data);
    }

    public function __construct(Vendor $model)
    {
        $this->model = $model::getModel();
    }

    protected function baseQuery()
    {
        return $this->model->withCount('hasVoucher');
    }
}
