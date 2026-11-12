<?php

namespace App\Properties;

trait LhpEntity
{
    public static function field_id_kebun()
    {
        return 'lhp_id_kebun';
    }

    public function getFieldIdKebunAttribute()
    {
        return $this->{static::field_id_kebun()};
    }

    public static function field_tanggal()
    {
        return 'lhp_tanggal';
    }

    public function getFieldTanggalAttribute()
    {
        return $this->{static::field_tanggal()};
    }
}
