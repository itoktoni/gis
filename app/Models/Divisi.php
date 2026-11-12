<?php

namespace App\Models;

class Divisi extends BaseModel
{
    protected $table = 'divisi';

    protected $primaryKey = 'divisi_id';

    protected $fillable = [
        'divisi_kode',
        'divisi_nama',
        'divisi_keterangan',
    ];

    public static $filterColumns = [
        'divisi_kode' => 'Kode',
        'divisi_nama' => 'Nama Divisi',
    ];

    public static $sortColumns = [
        'divisi_kode',
        'divisi_nama',
        'divisi_keterangan',
    ];

    public static function field_name(): string
    {
        return 'divisi_nama';
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'divisi_kode' => 'required|string|max:20|unique:divisi,divisi_kode,'.$ignore.',divisi_id',
            'divisi_nama' => 'required|string|max:255',
            'divisi_keterangan' => 'nullable|string|max:255',
        ];
    }

    public function hasDocuments()
    {
        return $this->belongsToMany(KmsDocument::class, 'kms_document_divisi', 'divisi_id', 'document_id');
    }

    public static function labelKodeNama(self $divisi): string
    {
        return $divisi->divisi_kode ? $divisi->divisi_kode.' - '.$divisi->divisi_nama : $divisi->divisi_nama;
    }

    public static function getCodeOptions()
    {
        return static::orderBy('divisi_kode')->orderBy('divisi_nama')->get()
            ->mapWithKeys(fn ($d) => [$d->divisi_id => static::labelKodeNama($d)]);
    }
}
