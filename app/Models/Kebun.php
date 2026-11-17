<?php

namespace App\Models;

class Kebun extends BaseModel
{
    protected $table = 'kebun';

    protected $primaryKey = 'kebun_id';

    protected $fillable = [
        'kebun_nama',
        'kebun_id_pt',
        'kebun_id_distrik',
        'kebun_luas_ha',
        'kebun_target_produksi_kg',
        'kebun_rkap_kg',
        'kebun_jumlah_pohon',
        'kebun_status',
        'kebun_kelola',
        'kebun_kabupaten',
        'kebun_lat',
        'kebun_lng',
        'kebun_polygon',
    ];

    public static $filterColumns = [
        'kebun_nama' => 'Nama',
        'kebun_id_pt' => 'PT / Grup',
        'kebun_id_distrik' => 'Distrik',
        'kebun_status' => 'Status',
    ];

    public static $sortColumns = [
        'kebun_nama',
        'kebun_id_pt',
        'kebun_id_distrik',
        'kebun_luas_ha',
        'kebun_target_produksi_kg',
        'kebun_rkap_kg',
        'kebun_jumlah_pohon',
        'kebun_status',
    ];

    protected function casts(): array
    {
        return [
            'kebun_luas_ha' => 'float',
            'kebun_target_produksi_kg' => 'float',
            'kebun_rkap_kg' => 'float',
            'kebun_jumlah_pohon' => 'integer',
            'kebun_lat' => 'float',
            'kebun_lng' => 'float',
        ];
    }

    public static function field_name(): string
    {
        return 'kebun_nama';
    }

    public function hasObjek()
    {
        return $this->hasMany(Objek::class, 'objek_id_kebun', 'kebun_id');
    }

    public function hasPt()
    {
        return $this->belongsTo(Pt::class, 'kebun_id_pt', 'pt_id');
    }

    public function hasDistrik()
    {
        return $this->belongsTo(Distrik::class, 'kebun_id_distrik', 'distrik_id');
    }

    public function hasLhp()
    {
        return $this->hasMany(Lhp::class, 'lhp_id_kebun', 'kebun_id');
    }

    public function hasVoucher()
    {
        return $this->hasMany(Voucher::class, 'voucher_id_kebun', 'kebun_id');
    }

    public function hasMasalah()
    {
        return $this->hasMany(Masalah::class, 'masalah_id_kebun', 'kebun_id');
    }

    public function rules(): array
    {
        return [
            'kebun_nama' => 'required|string|max:255',
            'kebun_id_pt' => 'nullable|exists:pt,pt_id',
            'kebun_id_distrik' => 'nullable|exists:distrik,distrik_id',
            'kebun_kelola' => 'nullable|string|max:20',
            'kebun_kabupaten' => 'nullable|string|max:50',
            'kebun_luas_ha' => 'nullable|numeric|min:0',
            'kebun_target_produksi_kg' => 'nullable|numeric|min:0',
            'kebun_rkap_kg' => 'nullable|numeric|min:0',
            'kebun_jumlah_pohon' => 'nullable|integer|min:0',
            'kebun_status' => 'nullable|string|max:50',
            'kebun_lat' => 'nullable|numeric|between:-90,90',
            'kebun_lng' => 'nullable|numeric|between:-180,180',
            'kebun_polygon' => ['nullable', 'string', 'max:50000', function ($attr, $value, $fail) {
                if (empty($value)) {
                    return;
                }
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('Polygon harus GeoJSON valid.');

                    return;
                }
                $type = $decoded['type'] ?? '';
                if (! in_array($type, ['Polygon', 'Feature'])) {
                    $fail('Polygon harus bertipe Polygon atau Feature.');
                }
            }],
        ];
    }
}
