<?php

namespace App\Models;

class Cuti extends BaseModel
{
    protected $table = 'cuti';

    protected $primaryKey = 'cuti_id';

    protected $fillable = [
        'cuti_id_user',
        'cuti_bagian',
        'cuti_jenis',
        'cuti_tanggal_mulai',
        'cuti_tanggal_selesai',
        'cuti_jumlah_hari',
        'cuti_keperluan',
        'cuti_alamat',
        'cuti_status',
        'cuti_id_approver',
        'cuti_tanggal_approve',
        'cuti_catatan',
        'cuti_file',
    ];

    public static $filterColumns = [
        'cuti_jenis' => 'Jenis',
        'cuti_status' => 'Status',
        'cuti_keperluan' => 'Keperluan',
    ];

    public static $sortColumns = [
        'cuti_jenis',
        'cuti_tanggal_mulai',
        'cuti_tanggal_selesai',
        'cuti_jumlah_hari',
        'cuti_status',
    ];

    protected function casts(): array
    {
        return [
            'cuti_tanggal_mulai' => 'date',
            'cuti_tanggal_selesai' => 'date',
            'cuti_tanggal_approve' => 'datetime',
        ];
    }

    public static function field_name(): string
    {
        return 'cuti_keperluan';
    }

    public function rules(): array
    {
        return [
            'cuti_id_user' => 'required|exists:users,id',
            'cuti_bagian' => 'nullable|string|max:100',
            'cuti_jenis' => 'required|string|max:50',
            'cuti_tanggal_mulai' => 'required|date',
            'cuti_tanggal_selesai' => 'required|date|after_or_equal:cuti_tanggal_mulai',
            'cuti_keperluan' => 'required|string',
            'cuti_alamat' => 'nullable|string|max:255',
            'cuti_status' => 'nullable|string|max:50',
            'cuti_catatan' => 'nullable|string',
            'cuti_file' => 'nullable|string|max:255',
        ];
    }

    public function getFileUrlAttribute(): string
    {
        return fileUrl($this->cuti_file);
    }

    public function hasUser()
    {
        return $this->belongsTo(User::class, 'cuti_id_user', 'id');
    }

    public function hasApprover()
    {
        return $this->belongsTo(User::class, 'cuti_id_approver', 'id');
    }

    // Kuota tahunan default 12 hari
    public static function kuota(int $year = null): int
    {
        return 12;
    }

    // Sisa cuti user pada tahun berjalan (kuota - hari approved jenis tahunan)
    public static function sisa(int $userId, int $year = null): int
    {
        $year = $year ?? (int) now()->format('Y');
        $terpakai = static::where('cuti_id_user', $userId)
            ->where('cuti_status', 'approved')
            ->where('cuti_jenis', 'tahunan')
            ->whereYear('cuti_tanggal_mulai', $year)
            ->sum('cuti_jumlah_hari');

        return max(0, static::kuota($year) - (int) $terpakai);
    }
}
