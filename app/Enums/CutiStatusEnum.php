<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class CutiStatusEnum extends Enum
{
    use EnumTrait;

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::PENDING => 'Menunggu',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            default => parent::getDescription($value),
        };
    }
}
