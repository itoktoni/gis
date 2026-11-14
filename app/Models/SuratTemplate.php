<?php

namespace App\Models;

class SuratTemplate extends BaseModel
{
    protected $table = 'surat_template';

    protected $primaryKey = 'surat_template_id';

    protected $fillable = [
        'surat_template_kode',
        'surat_template_nama',
        'surat_template_isi',
        'surat_template_keterangan',
    ];

    public static $filterColumns = [
        'surat_template_kode' => 'Kode',
        'surat_template_nama' => 'Nama Template',
    ];

    public static $sortColumns = [
        'surat_template_kode',
        'surat_template_nama',
        'surat_template_keterangan',
    ];

    public static function field_name(): string
    {
        return 'surat_template_nama';
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'surat_template_kode' => 'required|string|max:20|unique:surat_template,surat_template_kode,'.$ignore.',surat_template_id',
            'surat_template_nama' => 'required|string|max:255',
            'surat_template_isi' => 'required|string',
            'surat_template_keterangan' => 'nullable|string|max:255',
        ];
    }

    public function hasSurat()
    {
        return $this->hasMany(Surat::class, 'surat_id_template', 'surat_template_id');
    }

    public function getPlaceholdersAttribute(): array
    {
        return app(\App\Services\SuratNomorService::class)->placeholders($this->surat_template_isi);
    }
}
