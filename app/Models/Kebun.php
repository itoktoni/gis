<?php

namespace App\Models;

class Kebun extends BaseModel
{
    protected $table = 'kebun';

    protected $primaryKey = 'kebun_id';

    protected $fillable = [
        'kebun_nama',
        'kebun_luas_ha',
        'kebun_jumlah_pohon',
        'kebun_status',
        'kebun_lat',
        'kebun_lng',
        'kebun_polygon',
    ];

    public static $filterColumns = [
        'kebun_nama' => 'Nama',
        'kebun_status' => 'Status',
    ];

    public static $sortColumns = [
        'kebun_nama',
        'kebun_luas_ha',
        'kebun_jumlah_pohon',
        'kebun_status',
    ];

    protected function casts(): array
    {
        return [
            'kebun_luas_ha' => 'float',
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

    public function rules(): array
    {
        return [
            'kebun_nama' => 'required|string|max:255',
            'kebun_luas_ha' => 'nullable|numeric|min:0',
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
