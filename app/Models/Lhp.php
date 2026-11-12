<?php

namespace App\Models;

use App\Properties\LhpEntity;
use Illuminate\Validation\Rule;

class Lhp extends BaseModel
{
    use LhpEntity;

    protected $table = 'lhp';

    protected $primaryKey = 'lhp_id';

    protected $fillable = [
        'lhp_id_kebun',
        'lhp_tanggal',
        'lhp_hari',
        'lhp_luas_tm',
        'lhp_luas_panen',
        'lhp_tk_hk',
        'lhp_janjang_panen',
        'lhp_produksi_panen_kg',
        'lhp_janjang_kirim',
        'lhp_produksi_kirim_kg',
    ];

    public static $filterColumns = [
        'lhp_tanggal' => 'Tanggal',
        'lhp_id_kebun' => 'Kebun',
    ];

    public static $sortColumns = [
        'lhp_tanggal',
        'lhp_id_kebun',
        'lhp_luas_panen',
        'lhp_tk_hk',
        'lhp_janjang_panen',
        'lhp_produksi_panen_kg',
        'lhp_janjang_kirim',
        'lhp_produksi_kirim_kg',
    ];

    protected function casts(): array
    {
        return [
            'lhp_tanggal' => 'date',
            'lhp_luas_tm' => 'float',
            'lhp_luas_panen' => 'float',
            'lhp_tk_hk' => 'float',
            'lhp_janjang_panen' => 'integer',
            'lhp_produksi_panen_kg' => 'float',
            'lhp_janjang_kirim' => 'integer',
            'lhp_produksi_kirim_kg' => 'float',
        ];
    }

    public static function field_name(): string
    {
        return 'lhp_tanggal';
    }

    public function rules(): array
    {
        $kebunId = request()->input('lhp_id_kebun', $this->lhp_id_kebun ?? null);
        $ignoreId = request()->route('id');

        return [
            'lhp_id_kebun' => 'required|exists:kebun,kebun_id',
            'lhp_tanggal' => [
                'required',
                'date',
                Rule::unique('lhp', 'lhp_tanggal')
                    ->where(fn ($q) => $q->where('lhp_id_kebun', $kebunId))
                    ->ignore($ignoreId, 'lhp_id'),
            ],
            'lhp_hari' => 'nullable|string|max:15',
            'lhp_luas_tm' => 'nullable|numeric|min:0',
            'lhp_luas_panen' => 'nullable|numeric|min:0',
            'lhp_tk_hk' => 'nullable|numeric|min:0',
            'lhp_janjang_panen' => 'nullable|integer|min:0',
            'lhp_produksi_panen_kg' => 'nullable|numeric|min:0',
            'lhp_janjang_kirim' => 'nullable|integer|min:0',
            'lhp_produksi_kirim_kg' => 'nullable|numeric|min:0',
        ];
    }

    public function hasKebun()
    {
        return $this->belongsTo(Kebun::class, 'lhp_id_kebun', 'kebun_id');
    }

    public function getBjrPanenAttribute(): float
    {
        if (empty($this->lhp_janjang_panen)) {
            return 0;
        }

        return round((float) $this->lhp_produksi_panen_kg / (float) $this->lhp_janjang_panen, 2);
    }

    public function getBjrKirimAttribute(): float
    {
        if (empty($this->lhp_janjang_kirim)) {
            return 0;
        }

        return round((float) $this->lhp_produksi_kirim_kg / (float) $this->lhp_janjang_kirim, 2);
    }
}
