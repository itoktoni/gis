<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class KmsDocumentStatusEnum extends Enum
{
    use EnumTrait;

    const PROCESSING = 'processing';

    const READY = 'ready';

    const FAILED = 'failed';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::PROCESSING => 'Memproses',
            self::READY => 'Siap',
            self::FAILED => 'Gagal',
            default => parent::getDescription($value),
        };
    }
}
