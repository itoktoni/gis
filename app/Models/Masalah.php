<?php

namespace App\Models;

class Masalah extends BaseModel
{
    protected $table = 'masalah';

    protected $primaryKey = 'masalah_id';

    protected $fillable = [
        'masalah_id_kebun',
        'masalah_tanggal',
        'masalah_permasalahan',
        'masalah_kondisi',
        'masalah_dampak',
        'masalah_penyebab',
        'masalah_rtl',
        'masalah_file',
        'masalah_status',
    ];

    public static $filterColumns = [
        'masalah_id_kebun' => 'Kebun',
        'masalah_status' => 'Status',
        'masalah_tanggal' => 'Tanggal',
    ];

    public static $sortColumns = [
        'masalah_tanggal',
        'masalah_id_kebun',
        'masalah_permasalahan',
        'masalah_status',
    ];

    protected function casts(): array
    {
        return [
            'masalah_tanggal' => 'date',
        ];
    }

    public static function field_name(): string
    {
        return 'masalah_permasalahan';
    }

    public function rules(): array
    {
        return [
            'masalah_id_kebun' => array_filter([
                'required',
                'exists:kebun,kebun_id',
                allowedKebunIds() ? \Illuminate\Validation\Rule::in(allowedKebunIds()) : null,
            ]),
            'masalah_tanggal' => 'nullable|date',
            'masalah_permasalahan' => 'required|string|max:500',
            'masalah_kondisi' => 'nullable|string',
            'masalah_dampak' => 'nullable|string',
            'masalah_penyebab' => 'nullable|string',
            'masalah_rtl' => 'nullable|string',
            'masalah_file' => 'nullable|string|max:255',
            'masalah_status' => 'nullable|string|max:20',
        ];
    }

    public function hasKebun()
    {
        return $this->belongsTo(Kebun::class, 'masalah_id_kebun', 'kebun_id');
    }

    public static function statusOptions(): array
    {
        return [
            'open' => 'Terbuka',
            'proses' => 'Diproses',
            'selesai' => 'Selesai',
        ];
    }

    public function getFileUrlAttribute(): string
    {
        return fileUrl($this->masalah_file);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->masalah_status] ?? $this->masalah_status;
    }

    public function scopeAktif($query)
    {
        return $query->where('masalah_status', '!=', 'selesai');
    }
}
