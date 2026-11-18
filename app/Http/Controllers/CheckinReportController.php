<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Checkin;
use App\Models\Kunjungan;
use App\Models\User;

class CheckinReportController extends Controller
{
    use ControllerTrait;

    public function __construct(Checkin $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'user_id' => ['hasUser', 'name'],
        ];
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'userOptions' => User::orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => [
                'valid' => 'Valid',
                'invalid' => 'Di Luar Jangkauan',
                'fake' => 'Palsu',
            ],
        ];

        return array_merge($default, $data);
    }

    protected function baseQuery()
    {
        $query = $this->model->with(['hasUser']);

        if (request()->filled('user_id')) {
            $query->where('user_id', request()->input('user_id'));
        }
        if (request()->filled('status')) {
            $query->where('status', request()->input('status'));
        }
        if (request()->filled('from')) {
            $query->whereDate('created_at', '>=', request()->input('from'));
        }
        if (request()->filled('to')) {
            $query->whereDate('created_at', '<=', request()->input('to'));
        }

        return $query;
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('created_at');
        }
    }

    public function getTable(GeneralRequest $request)
    {
        $data = $this->getData()->cursorPaginate($request->input('per_page', 25))->withQueryString();

        return $this->views($this->template(), [
            'data' => $data,
            'fields' => $this->getFields(),
        ]);
    }

    public function getShow(GeneralRequest $request, $id)
    {
        $checkin = $this->model->with(['hasUser'])->findOrFail($id);
        $kunjungan = Kunjungan::where('kunjungan_id_checkin', $checkin->id)->first();

        return $this->views($this->template(), array_merge($this->share(), [
            'model' => $checkin,
            'kunjungan' => $kunjungan,
        ]));
    }
}
