<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\VoucherStatus;

class VoucherStatusController extends Controller
{
    use ControllerTrait;

    public function __construct(VoucherStatus $model)
    {
        $this->model = $model::getModel();
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByRaw('voucher_status_no IS NULL, voucher_status_no');
        }
    }
}
