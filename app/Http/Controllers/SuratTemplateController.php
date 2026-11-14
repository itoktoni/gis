<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\SuratTemplate;

class SuratTemplateController extends Controller
{
    use ControllerTrait;

    public function __construct(SuratTemplate $model)
    {
        $this->model = $model::getModel();
    }

    protected function baseQuery()
    {
        return $this->model->withCount('hasSurat');
    }
}
