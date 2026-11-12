<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Distrik;

class DistrikController extends Controller
{
    use ControllerTrait;

    public function __construct(Distrik $model)
    {
        $this->model = $model::getModel();
    }
}
