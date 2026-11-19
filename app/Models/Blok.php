<?php

namespace App\Models;

class Blok extends BaseModel
{
    protected $table = 'blok';

    protected $primaryKey = 'blok_id';

    protected $fillable = [
        'blok_id_kebun',
        'blok_kode',
        'blok_nama',
        'blok_luas_ha',
        'blok_jumlah_pohon',
    ];

    public static $filterColumns = [
        'blok_kode' => 'Kode',
        'blok_nama' => 'Nama Blok',
        'blok_id_kebun' => 'Kebun',
    ];

    public static $sortColumns = [
        'blok_kode',
        'blok_nama',
        'blok_id_kebun',
        'blok_luas_ha',
        'blok_jumlah_pohon',
    ];

    protected function casts(): array
    {
        return [
            'blok_luas_ha' => 'float',
            'blok_jumlah_pohon' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'blok_nama';
    }

    public function rules(): array
    {
        return [
            'blok_id_kebun' => 'required|exists:kebun,kebun_id',
            'blok_kode' => 'required|string|max:20',
            'blok_nama' => 'required|string|max:255',
            'blok_luas_ha' => 'nullable|numeric|min:0',
            'blok_jumlah_pohon' => 'nullable|integer|min:0',
        ];
    }

    public function hasKebun()
    {
        return $this->belongsTo(Kebun::class, 'blok_id_kebun', 'kebun_id');
    }
}
