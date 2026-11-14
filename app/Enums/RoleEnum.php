<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class RoleEnum extends Enum
{
    use EnumTrait;

    const USER = 'user';

    const EDITOR = 'editor';

    const ADMIN = 'admin';

    const DEVELOPER = 'developer';

    const TANAMAN = 'tanaman';

    const FINANCE = 'finance';

    const SEKRETARIS = 'sekretaris';

    const DISTRIK = 'distrik';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::ADMIN => 'Administrator Utama',
            self::EDITOR => 'Editor',
            self::DEVELOPER => 'Developer',
            self::TANAMAN => 'Tim Tanaman (LHP)',
            self::FINANCE => 'Tim Finance (Voucher)',
            self::SEKRETARIS => 'Sekretaris (Surat)',
            self::DISTRIK => 'Distrik (Kebun & LHP)',
            self::USER => 'Pengguna Biasa',
            default => parent::getDescription($value),
        };
    }
}
