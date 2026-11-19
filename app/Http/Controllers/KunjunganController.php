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
            'kebunGeo' => Kebun::select('kebun_id as id', 'kebun_nama as nama', 'kebun_lat as lat', 'kebun_lng as lng', 'kebun_polygon as polygon')->get()->keyBy('id'),
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
        return $this->model->with(['hasUser', 'hasUsers', 'hasObjek', 'hasKebun']);
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
        $this->mergePrimaryUser($request);
        $this->assertKebunAllowed($request);

        $payload = \App\Actions\CreateAction::run($request, $this->model);
        if (! empty($payload['status']) && ! empty($payload['data'])) {
            $this->syncUsers($payload['data'], $request);
        }

        return $this->response($payload);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->mergeDefaults($request);
        $this->mergePrimaryUser($request);
        $this->assertKebunAllowed($request);

        $payload = \App\Actions\UpdateAction::run($request, $id, $this->model);
        if (! empty($payload['status']) && ! empty($payload['data'])) {
            $this->syncUsers($payload['data'], $request);
        }

        return $this->response($payload);
    }

    /**
     * Multi-user: user_ids[] (bisa banyak). Anggota pertama menjadi
     * kunjungan_id_user (kolom lama: kompatibilitas + validasi required).
     */
    private function mergePrimaryUser(GeneralRequest $request): void
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            array_filter((array) $request->input('user_ids', []))
        )));
        if ($ids !== []) {
            $request->merge(['kunjungan_id_user' => $ids[0]]);
        }
    }

    /** Sinkron pivot kunjungan_user dari pilihan multi-user (fallback: pemilik). */
    private function syncUsers(Kunjungan $row, GeneralRequest $request): void
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            array_filter((array) $request->input('user_ids', []))
        )));
        if ($ids === [] && $row->kunjungan_id_user) {
            $ids = [(int) $row->kunjungan_id_user];
        }
        if ($ids !== []) {
            $row->hasUsers()->sync($ids);
        }
    }

    /** Set NULL: kembalikan ke pending + lepas tautan check-in (bisa check-in ulang dari HP). */
    public function postReset(GeneralRequest $request, $id)
    {
        $row = $this->model->findOrFail($id);
        $row->update(['kunjungan_status' => 'pending', 'kunjungan_id_checkin' => null]);

        return $this->response($this->payload(TOAST_SUCCESS, $row));
    }

    public function getImport(GeneralRequest $request)
    {
        return $this->views($this->template('import'), []);
    }

    public function postImport(GeneralRequest $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv,txt|max:10240']);

        $path = $request->file('file')->getRealPath();
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $sheet = $ext === 'xlsx'
            ? \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->toArray()
            : $this->readDelimited($path);
        if (count($sheet) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'File kosong.']);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $sheet[0]);
        $col = function (array $names) use ($header) {
            foreach ($names as $n) {
                $i = array_search($n, $header);
                if ($i !== false) {
                    return $i;
                }
            }

            return null;
        };

        $cNama = $col(['nama titik', 'nama', 'titik']);
        $cUser = $col(['user (email)', 'user', 'email', 'satpam']);
        $cKebun = $col(['kebun']);
        $cObjek = $col(['objek', 'pos']);
        $cTgl = $col(['tanggal (y-m-d h:i)', 'tanggal', 'date']);
        $cLat = $col(['latitude', 'lat']);
        $cLng = $col(['longitude', 'lng', 'long']);
        $cRadius = $col(['radius (m)', 'radius']);
        $cStatus = $col(['status']);

        if ($cNama === null || $cUser === null || $cKebun === null || $cLat === null || $cLng === null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Header tidak dikenali. Gunakan template: Nama Titik, User (Email), Kebun, Objek, Tanggal, Latitude, Longitude, Radius, Status.']);
        }

        $masuk = 0;
        $gagal = 0;

        foreach (array_slice($sheet, 1) as $row) {
            try {
                $nama = trim((string) ($row[$cNama] ?? ''));
                $email = trim((string) ($row[$cUser] ?? ''));
                $kebunNama = trim((string) ($row[$cKebun] ?? ''));
                $lat = $this->parseCoordinate((string) ($row[$cLat] ?? ''), -90, 90);
                $lng = $this->parseCoordinate((string) ($row[$cLng] ?? ''), -180, 180);
                if ($nama === '' || $email === '' || $kebunNama === '' || $lat === null || $lng === null) {
                    $gagal++;

                    continue;
                }

                $user = User::where('email', $email)->first();
                $kebun = Kebun::where('kebun_nama', $kebunNama)->first();
                if (! $user || ! $kebun) {
                    $gagal++;

                    continue;
                }

                $objekNama = trim((string) ($row[$cObjek] ?? ''));
                $objekId = $objekNama !== ''
                    ? Objek::where('objek_nama', $objekNama)->value('objek_id')
                    : null;

                $tglRaw = trim((string) ($row[$cTgl] ?? ''));
                $tgl = $this->parseTanggal($tglRaw);
                if ($tglRaw !== '' && $tgl === null) {
                    $gagal++;

                    continue;
                }

                $status = strtolower(trim((string) ($row[$cStatus] ?? 'pending')));
                if (! in_array($status, ['pending', 'done'], true)) {
                    $status = 'pending';
                }

                $radiusRaw = trim((string) ($row[$cRadius] ?? ''));
                $radius = is_numeric(str_replace(',', '.', $radiusRaw)) ? (int) str_replace(',', '.', $radiusRaw) : 100;

                $k = $this->model->create([
                    'kunjungan_id_user' => $user->id,
                    'kunjungan_id_objek' => $objekId,
                    'kunjungan_id_kebun' => $kebun->kebun_id,
                    'kunjungan_tanggal' => $tgl,
                    'kunjungan_nama' => $nama,
                    'kunjungan_lat' => $lat,
                    'kunjungan_lng' => $lng,
                    'kunjungan_radius_m' => $radius,
                    'kunjungan_status' => $status,
                ]);
                $k->hasUsers()->sync([$user->id]);

                $masuk++;
            } catch (\Throwable) {
                $gagal++;
            }
        }

        return $this->response($this->payload(TOAST_SUCCESS, "Import selesai: {$masuk} masuk, {$gagal} gagal."));
    }

    /** Baca CSV/TXT: deteksi pemisah otomatis (tab, titik-koma, koma, pipe). */
    private function readDelimited(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if ($lines === []) {
            return [];
        }
        // BOM Windows Excel
        $lines[0] = ltrim($lines[0], "\xEF\xBB\xBF");
        $counts = [];
        foreach ([',', ';', "\t", '|'] as $d) {
            $counts[$d] = substr_count($lines[0], $d);
        }
        arsort($counts);
        $delim = array_key_first($counts);
        if (($counts[$delim] ?? 0) === 0) {
            $delim = ',';
        }

        return array_map(fn ($l) => str_getcsv($l, $delim), $lines);
    }

    /**
     * Koordinat toleran format Indonesia: "−2,719885" maupun "−2.719.885"
     * (titik ribuan) diterima; dipilih kandidat pertama yang masuk rentang.
     */
    private function parseCoordinate(string $raw, float $min, float $max): ?float
    {
        $v = trim($raw);
        if ($v === '') {
            return null;
        }
        $candidates = [
            str_replace(',', '.', $v),                       // "−2,719885" / polos
            str_replace(',', '.', str_replace('.', '', $v)), // titik = ribuan
        ];
        // Hanya titik pertama desimal, sisanya ribuan: "111.643.005" -> 111.643005
        if (substr_count($v, '.') > 1) {
            $pos = strpos($v, '.');
            $candidates[] = substr($v, 0, $pos).'.'.str_replace('.', '', substr($v, $pos + 1));
        }
        foreach ($candidates as $c) {
            if (is_numeric($c)) {
                $f = (float) $c;
                if ($f >= $min && $f <= $max) {
                    return $f;
                }
            }
        }

        return null;
    }

    /**
     * Tanggal fleksibel: "20/11/2026 06:00", "20-11-2026", "2026-11-20",
     * dengan atau tanpa jam. Kosong = null (berlaku selamanya).
     */
    private function parseTanggal(string $raw): ?string
    {
        $v = trim($raw);
        if ($v === '') {
            return null;
        }
        foreach (['d/m/Y H:i', 'd/m/Y H:i:s', '!d/m/Y', 'd-m-Y H:i', 'd-m-Y H:i:s', '!d-m-Y', 'Y-m-d H:i:s', 'Y-m-d H:i', '!Y-m-d'] as $f) {
            try {
                return \Carbon\Carbon::createFromFormat($f, $v)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
            }
        }
        try {
            return \Carbon\Carbon::parse($v)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Tolak titik di kebun yang tidak boleh diakses user tersebut.
     * User tanpa mapping (allowedKebunIds null) boleh ke semua kebun.
     * Dicek untuk SEMUA user terpilih (multi-user).
     */
    private function assertKebunAllowed(GeneralRequest $request): void
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            array_filter((array) $request->input('user_ids', [$request->input('kunjungan_id_user')]))
        )));
        foreach ($ids as $uid) {
            $user = User::find($uid);
            if (! $user) {
                continue;
            }
            $allowed = $user->allowedKebunIds();
            if ($allowed === null) {
                continue;
            }
            $kebunId = $request->input('kunjungan_id_kebun');
            if (! $kebunId && $request->filled('kunjungan_id_objek')) {
                $kebunId = Objek::where('objek_id', $request->input('kunjungan_id_objek'))->value('objek_id_kebun');
            }
            if ($kebunId && ! in_array((int) $kebunId, $allowed, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'kunjungan_id_kebun' => "User {$user->name} tidak memiliki akses ke kebun tersebut (cek mapping user-kebun).",
                ]);
            }
        }
    }

    private function mergeDefaults(GeneralRequest $request): void
    {
        if (! $request->filled('kunjungan_radius_m')) {
            $request->merge(['kunjungan_radius_m' => 100]);
        }
        // Polygon GeoJSON mentah (tempel QGIS/UTM) dinormalisasi ke Polygon lat/lng.
        if ($request->filled('kunjungan_polygon')) {
            $norm = \App\Services\UtmConverter::normalizePolygon((string) $request->input('kunjungan_polygon'));
            $request->merge(['kunjungan_polygon' => $norm ?? $request->input('kunjungan_polygon')]);
        }
        // Pilihan "-- Null --" (string kosong) disimpan sebagai NULL, bukan pending.
        if ($request->input('kunjungan_status') === '') {
            $request->merge(['kunjungan_status' => null]);
        } elseif (! $request->exists('kunjungan_status')) {
            $request->merge(['kunjungan_status' => 'pending']);
        }
    }
}
