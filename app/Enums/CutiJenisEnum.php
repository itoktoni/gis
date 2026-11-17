<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class CutiJenisEnum extends Enum
{
    use EnumTrait;

    const TAHUNAN = 'tahunan';
    const SAKIT = 'sakit';
    const IZIN = 'izin';
    const MELAHIRKAN = 'melahirkan';
    const BESAR = 'besar';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::TAHUNAN => 'Cuti Tahunan',
            self::SAKIT => 'Cuti Sakit',
            self::IZIN => 'Izin',
            self::MELAHIRKAN => 'Cuti Melahirkan',
            self::BESAR => 'Cuti Besar',
            default => parent::getDescription($value),
        };
    }
}
