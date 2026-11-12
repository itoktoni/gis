<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Pt;

class PtController extends Controller
{
    use ControllerTrait;

    public function __construct(Pt $model)
    {
        $this->model = $model::getModel();
    }
}
