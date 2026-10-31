<?php

namespace App\Models;

class Objek extends BaseModel
{
    protected $table = 'objek';

    protected $primaryKey = 'objek_id';

    protected $fillable = [
        'objek_nama',
        'objek_jenis',
        'objek_lat',
        'objek_lng',
        'objek_id_kebun',
    ];

    public static $filterColumns = [
        'objek_nama' => 'Nama',
        'objek_jenis' => 'Jenis',
    ];

    public static $sortColumns = [
        'objek_nama',
        'objek_jenis',
    ];

    protected function casts(): array
    {
        return [
            'objek_lat' => 'float',
            'objek_lng' => 'float',
            'objek_id_kebun' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'objek_nama';
    }

    public function rules(): array
    {
        return [
            'objek_nama' => 'required|string|max:255',
            'objek_jenis' => 'required|string|max:50',
            'objek_lat' => 'required|numeric|between:-90,90',
            'objek_lng' => 'required|numeric|between:-180,180',
            'objek_id_kebun' => 'nullable|integer|exists:kebun,kebun_id',
        ];
    }

    public function hasKebun()
    {
        return $this->belongsTo(Kebun::class, 'objek_id_kebun', 'kebun_id');
    }
}