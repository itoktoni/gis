<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Kunjungan extends BaseModel
{
    use HasUuids;

    protected $table = 'kunjungan';

    protected $primaryKey = 'kunjungan_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'kunjungan_id_user', 'kunjungan_id_objek', 'kunjungan_id_kebun',
        'kunjungan_tanggal', 'kunjungan_nama', 'kunjungan_lat', 'kunjungan_lng',
        'kunjungan_radius_m', 'kunjungan_polygon', 'kunjungan_status', 'kunjungan_id_checkin',
    ];

    public static $filterColumns = [
        'kunjungan_nama' => 'Nama',
        'kunjungan_tanggal' => 'Tanggal',
        'kunjungan_status' => 'Status',
    ];

    public static $sortColumns = [
        'kunjungan_tanggal',
        'kunjungan_nama',
        'kunjungan_status',
    ];

    protected function casts(): array
    {
        return [
            'kunjungan_tanggal' => 'datetime',
            'kunjungan_lat' => 'float',
            'kunjungan_lng' => 'float',
        ];
    }

    public static function field_name(): string
    {
        return 'kunjungan_nama';
    }

    public function rules(): array
    {
        return [
            'kunjungan_id_user' => 'required|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'kunjungan_tanggal' => 'nullable|date',
            'kunjungan_nama' => 'required|string|max:255',
            'kunjungan_lat' => 'required|numeric|between:-90,90',
            'kunjungan_lng' => 'required|numeric|between:-180,180',
            'kunjungan_radius_m' => 'nullable|integer|min:10|max:5000',
            'kunjungan_polygon' => ['nullable', 'string', 'max:500000', function ($attr, $value, $fail) {
                if (empty($value)) {
                    return;
                }
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('Polygon harus GeoJSON valid.');
                }
            }],
            'kunjungan_status' => 'nullable|string|in:pending,done',
        ];
    }

    public function hasUser()
    {
        return $this->belongsTo(User::class, 'kunjungan_id_user', 'id');
    }

    /** Banyak user per 1 titik (pivot kunjungan_user). */
    public function hasUsers()
    {
        return $this->belongsToMany(User::class, 'kunjungan_user', 'kunjungan_id', 'user_id', 'kunjungan_id', 'id')
            ->withTimestamps();
    }

    /** Semua user yang boleh absen di titik ini: anggota pivot + pemilik lama. */
    public function assignedUserIds(): array
    {
        $ids = $this->hasUsers()->pluck('users.id')->all();
        if ($this->kunjungan_id_user) {
            $ids[] = (int) $this->kunjungan_id_user;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function hasObjek()
    {
        return $this->belongsTo(Objek::class, 'kunjungan_id_objek', 'objek_id');
    }

    public function hasKebun()
    {
        return $this->belongsTo(Kebun::class, 'kunjungan_id_kebun', 'kebun_id');
    }
}
