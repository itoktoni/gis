<?php

namespace App\Models;

class Vendor extends BaseModel
{
    protected $table = 'vendor';

    protected $primaryKey = 'vendor_id';

    protected $fillable = [
        'vendor_kode',
        'vendor_nama',
        'vendor_jenis',
        'vendor_telepon',
        'vendor_alamat',
        'vendor_rekening',
        'vendor_keterangan',
    ];

    public static $filterColumns = [
        'vendor_kode' => 'Kode',
        'vendor_nama' => 'Nama Vendor',
        'vendor_jenis' => 'Jenis',
    ];

    public static $sortColumns = [
        'vendor_kode',
        'vendor_nama',
        'vendor_jenis',
        'vendor_telepon',
    ];

    public static function field_name(): string
    {
        return 'vendor_nama';
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'vendor_kode' => 'required|string|max:20|unique:vendor,vendor_kode,'.$ignore.',vendor_id',
            'vendor_nama' => 'required|string|max:255',
            'vendor_jenis' => 'required|string|max:20',
            'vendor_telepon' => 'nullable|string|max:30',
            'vendor_alamat' => 'nullable|string|max:255',
            'vendor_rekening' => 'nullable|string|max:100',
            'vendor_keterangan' => 'nullable|string|max:255',
        ];
    }

    public function hasVoucher()
    {
        return $this->hasMany(Voucher::class, 'voucher_id_vendor', 'vendor_id');
    }

    public static function labelKodeNama(self $vendor): string
    {
        return $vendor->vendor_kode ? $vendor->vendor_kode.' - '.$vendor->vendor_nama : $vendor->vendor_nama;
    }

    public static function getCodeOptions()
    {
        return static::orderBy('vendor_kode')->orderBy('vendor_nama')->get()
            ->mapWithKeys(fn ($v) => [$v->vendor_id => static::labelKodeNama($v)]);
    }
}
