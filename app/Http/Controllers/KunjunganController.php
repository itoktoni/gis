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

        $response = $this->traitPostCreate($request);
        $this->syncUsers($request);

        return $response;
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->mergeDefaults($request);
        $this->assertKebunAllowed($request);

        $response = $this->traitPostUpdate($request, $id);
        $this->syncUsers($request, $id);

        return $response;
    }

    /**
     * Sinkron pivot kunjungan_user dari pilihan multi-user.
     * kunjungan_id_user (kolom lama) diisi anggota pertama untuk kompatibilitas
     * API HP lama + validasi required.
     */
    private function syncUsers(GeneralRequest $request, $id = null): void
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            array_filter((array) $request->input('user_ids', []))
        ));
        if ($ids === []) {
            $first = $request->input('kunjungan_id_user');
            if ($first) {
                $ids = [(int) $first];
            }
        }
        if ($ids === []) {
            return;
        }

        $rowId = $id ?? $this->model->latest('kunjungan_id')->value('kunjungan_id');
        // Data baru: ambil id dari record yang baru dibuat (pemilik = anggota pertama).
        if (! $id) {
            $row = $this->model->where('kunjungan_id_user', $ids[0])->latest()->first();
            $rowId = $row?->kunjungan_id ?? $rowId;
        }
        if (! $rowId) {
            return;
        }

        $k = $this->model->find($rowId);
        if ($k) {
            $k->hasUsers()->sync($ids);
            // Pastikan pemilik termasuk anggota + kolom lama valid.
            if ((int) $k->kunjungan_id_user !== $ids[0]) {
                $k->update(['kunjungan_id_user' => $ids[0]]);
            }
        }
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
