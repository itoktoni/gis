<?php

namespace App\Models;

class LhpBlok extends BaseModel
{
    protected $table = 'lhp_blok';

    protected $primaryKey = 'lhp_blok_id';

    protected $fillable = [
        'lhp_blok_id_lhp',
        'lhp_blok_id_blok',
        'lhp_blok_luas_panen',
        'lhp_blok_janjang_panen',
        'lhp_blok_janjang_kirim',
        'lhp_blok_brondolan_panen',
        'lhp_blok_brondolan_kirim',
        'lhp_blok_restan',
    ];

    protected function casts(): array
    {
        return [
            'lhp_blok_luas_panen' => 'float',
            'lhp_blok_janjang_panen' => 'integer',
            'lhp_blok_janjang_kirim' => 'integer',
            'lhp_blok_brondolan_panen' => 'float',
            'lhp_blok_brondolan_kirim' => 'float',
            'lhp_blok_restan' => 'boolean',
        ];
    }

    public static function field_name(): string
    {
        return 'lhp_blok_id';
    }

    public function rules(): array
    {
        return [];
    }

    public function hasLhp()
    {
        return $this->belongsTo(Lhp::class, 'lhp_blok_id_lhp', 'lhp_id');
    }

    public function hasBlok()
    {
        return $this->belongsTo(Blok::class, 'lhp_blok_id_blok', 'blok_id');
    }
}
