<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Kebun;
use App\Models\Kunjungan;
use App\Models\Objek;
use App\Models\User;

class KunjunganController extends Controller
{
    use ControllerTrait {
        postCreate as protected traitPostCreate;
        postUpdate as protected traitPostUpdate;
    }

    public function __construct(Kunjungan $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'kunjungan_id_user' => ['hasUser', 'name'],
        ];
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'userOptions' => User::getOptions(),
            'kebunOptions' => Kebun::getOptions(),
            'kebunGeo' => Kebun::select('kebun_id as id', 'kebun_nama as nama', 'kebun_lat as lat', 'kebun_lng as lng')->get()->keyBy('id'),
            'objekOptions' => Objek::getOptions(),
            'objekGeo' => Objek::select('objek_id as id', 'objek_nama as nama', 'objek_lat as lat', 'objek_lng as lng')->get()->keyBy('id'),
            'statusOptions' => [
                'pending' => 'Pending',
                'done' => 'Done',
            ],
        ];

        return array_merge($default, $data);
    }

    protected function baseQuery()
    {
        return $this->model->with(['hasUser', 'hasObjek', 'hasKebun']);
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('kunjungan_tanggal');
        }
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->mergeDefaults($request);
        $this->assertKebunAllowed($request);

        return $this->traitPostCreate($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->mergeDefaults($request);
        $this->assertKebunAllowed($request);

        return $this->traitPostUpdate($request, $id);
    }

    /** Set NULL: kembalikan ke pending + lepas tautan check-in (bisa check-in ulang dari HP). */
    public function postReset(GeneralRequest $request, $id)
    {
        $row = $this->model->findOrFail($id);
        $row->update(['kunjungan_status' => 'pending', 'kunjungan_id_checkin' => null]);

        return $this->response($this->payload(TOAST_SUCCESS, $row));
    }

    /**
     * Tolak titik di kebun yang tidak boleh diakses user tersebut.
     * User tanpa mapping (allowedKebunIds null) boleh ke semua kebun.
     */
    private function assertKebunAllowed(GeneralRequest $request): void
    {
        $user = User::find($request->input('kunjungan_id_user'));
        if (! $user) {
            return;
        }
        $allowed = $user->allowedKebunIds();
        if ($allowed === null) {
            return;
        }
        $kebunId = $request->input('kunjungan_id_kebun');
        if (! $kebunId && $request->filled('kunjungan_id_objek')) {
            $kebunId = Objek::where('objek_id', $request->input('kunjungan_id_objek'))->value('objek_id_kebun');
        }
        if ($kebunId && ! in_array((int) $kebunId, $allowed, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'kunjungan_id_kebun' => 'User ini tidak memiliki akses ke kebun tersebut (cek mapping user-kebun).',
            ]);
        }
    }

    private function mergeDefaults(GeneralRequest $request): void
    {
        if (! $request->filled('kunjungan_radius_m')) {
            $request->merge(['kunjungan_radius_m' => 100]);
        }
        // Pilihan "-- Null --" (string kosong) disimpan sebagai NULL, bukan pending.
        if ($request->input('kunjungan_status') === '') {
            $request->merge(['kunjungan_status' => null]);
        } elseif (! $request->exists('kunjungan_status')) {
            $request->merge(['kunjungan_status' => 'pending']);
        }
    }
}
