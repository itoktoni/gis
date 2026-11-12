<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Divisi;

class DivisiController extends Controller
{
    use ControllerTrait;

    public function __construct(Divisi $model)
    {
        $this->model = $model::getModel();
    }

    protected function baseQuery()
    {
        return $this->model->withCount('hasDocuments');
    }
}
