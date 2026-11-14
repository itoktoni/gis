<?php

namespace App\Models;

class Surat extends BaseModel
{
    protected $table = 'surat';

    protected $primaryKey = 'surat_id';

    protected $fillable = [
        'surat_jenis',
        'surat_kode_jenis',
        'surat_nomor',
        'surat_urut',
        'surat_tanggal',
        'surat_lampiran',
        'surat_id_divisi',
        'surat_id_template',
        'surat_perihal',
        'surat_dari',
        'surat_kepada',
        'surat_data',
        'surat_isi',
        'surat_file',
        'surat_keterangan',
    ];

    public static $filterColumns = [
        'surat_jenis' => 'Jenis',
        'surat_tanggal' => 'Tanggal',
        'surat_id_divisi' => 'Divisi',
        'surat_nomor' => 'Nomor',
    ];

    public static $sortColumns = [
        'surat_nomor',
        'surat_jenis',
        'surat_tanggal',
        'surat_perihal',
        'surat_dari',
        'surat_kepada',
    ];

    protected function casts(): array
    {
        return [
            'surat_tanggal' => 'date',
            'surat_data' => 'array',
        ];
    }

    public static function field_name(): string
    {
        return 'surat_nomor';
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'surat_jenis' => 'required|in:MASUK,KELUAR',
            'surat_nomor' => 'nullable|string|max:255|unique:surat,surat_nomor,'.$ignore.',surat_id',
            'surat_tanggal' => 'required|date',
            'surat_lampiran' => 'nullable|string|max:50',
            'surat_id_divisi' => 'nullable|exists:divisi,divisi_id',
            'surat_id_template' => 'nullable|exists:surat_template,surat_template_id',
            'surat_perihal' => 'nullable|string|max:255',
            'surat_dari' => 'nullable|string|max:255',
            'surat_kepada' => 'nullable|string|max:255',
            'surat_data' => 'nullable|array',
            'surat_isi' => 'nullable|string',
            'surat_file' => 'nullable|string|max:255',
            'surat_keterangan' => 'nullable|string|max:255',
        ];
    }

    public function hasDivisi()
    {
        return $this->belongsTo(Divisi::class, 'surat_id_divisi', 'divisi_id');
    }

    public function hasTemplate()
    {
        return $this->belongsTo(SuratTemplate::class, 'surat_id_template', 'surat_template_id');
    }

    public function getFileUrlAttribute(): string
    {
        return fileUrl($this->surat_file);
    }
}
