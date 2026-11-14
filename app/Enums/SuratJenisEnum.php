<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class SuratJenisEnum extends Enum
{
    use EnumTrait;

    const MASUK = 'MASUK';
    const KELUAR = 'KELUAR';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::MASUK => 'Surat Masuk',
            self::KELUAR => 'Surat Keluar',
            default => parent::getDescription($value),
        };
    }
}
