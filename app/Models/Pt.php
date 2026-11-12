<?php

namespace App\Models;

class Pt extends BaseModel
{
    protected $table = 'pt';

    protected $primaryKey = 'pt_id';

    protected $fillable = [
        'pt_kode',
        'pt_nama',
        'pt_keterangan',
    ];

    public static $filterColumns = [
        'pt_kode' => 'Kode',
        'pt_nama' => 'Nama PT',
    ];

    public static $sortColumns = [
        'pt_kode',
        'pt_nama',
        'pt_keterangan',
    ];

    public static function field_name(): string
    {
        return 'pt_nama';
    }

    public static function labelKodeNama(self $pt): string
    {
        return $pt->pt_kode ? $pt->pt_kode.' - '.$pt->pt_nama : $pt->pt_nama;
    }

    public static function getCodeOptions()
    {
        return static::orderBy('pt_kode')->orderBy('pt_nama')->get()
            ->mapWithKeys(fn ($pt) => [$pt->pt_id => static::labelKodeNama($pt)]);
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'pt_kode' => 'required|string|max:20|unique:pt,pt_kode,'.$ignore.',pt_id',
            'pt_nama' => 'required|string|max:255|unique:pt,pt_nama,'.$ignore.',pt_id',
            'pt_keterangan' => 'nullable|string|max:255',
        ];
    }

    public function hasKebun()
    {
        return $this->hasMany(Kebun::class, 'kebun_id_pt', 'pt_id');
    }
}
