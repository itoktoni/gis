<?php

namespace App\Models;

class Distrik extends BaseModel
{
    protected $table = 'distrik';

    protected $primaryKey = 'distrik_id';

    protected $fillable = [
        'distrik_kode',
        'distrik_nama',
        'distrik_keterangan',
    ];

    public static $filterColumns = [
        'distrik_kode' => 'Kode',
        'distrik_nama' => 'Nama Distrik',
    ];

    public static $sortColumns = [
        'distrik_kode',
        'distrik_nama',
        'distrik_keterangan',
    ];

    public static function field_name(): string
    {
        return 'distrik_nama';
    }

    public static function labelKodeNama(self $distrik): string
    {
        return $distrik->distrik_kode ? $distrik->distrik_kode.' - '.$distrik->distrik_nama : $distrik->distrik_nama;
    }

    public static function getCodeOptions()
    {
        return static::orderBy('distrik_kode')->orderBy('distrik_nama')->get()
            ->mapWithKeys(fn ($d) => [$d->distrik_id => static::labelKodeNama($d)]);
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'distrik_kode' => 'required|string|max:20|unique:distrik,distrik_kode,'.$ignore.',distrik_id',
            'distrik_nama' => 'required|string|max:255|unique:distrik,distrik_nama,'.$ignore.',distrik_id',
            'distrik_keterangan' => 'nullable|string|max:255',
        ];
    }

    public function hasKebun()
    {
        return $this->hasMany(Kebun::class, 'kebun_id_distrik', 'distrik_id');
    }
}
