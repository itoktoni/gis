<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use App\Models\VoucherStatus;
use BenSampo\Enum\Enum;

final class VoucherStatusEnum extends Enum
{
    use EnumTrait;

    const DRAFT = 'draft';

    const VERIFIKASI_FA = 'verifikasi_fa';

    const VERIFIKASI_AP = 'verifikasi_ap';

    const KIRIM_JAKARTA = 'kirim_jakarta';

    const SIAP_BAYAR = 'siap_bayar';

    const SELESAI = 'selesai';

    const REVISI = 'revisi';

    /** Baris master per kode, cache per request. Null = master belum siap. */
    protected static ?array $masterRows = null;

    protected static function master(): ?array
    {
        if (static::$masterRows !== null) {
            return static::$masterRows;
        }

        try {
            $rows = VoucherStatus::all();
            if ($rows->isEmpty()) {
                return static::$masterRows = null;
            }
            static::$masterRows = $rows->keyBy('voucher_status_kode')->all();
        } catch (\Throwable) {
            static::$masterRows = null;
        }

        return static::$masterRows;
    }

    protected static function fallbackNames(): array
    {
        return [
            self::DRAFT => 'Draf',
            self::VERIFIKASI_FA => 'Verifikasi FA Site',
            self::VERIFIKASI_AP => 'Verifikasi Account Payable',
            self::KIRIM_JAKARTA => 'Dalam Pengiriman ke Jakarta',
            self::SIAP_BAYAR => 'Siap Bayar (Meja Kasir)',
            self::SELESAI => 'Selesai (Dibayar)',
            self::REVISI => 'Revisi (Ditolak)',
        ];
    }

    protected static function fallbackNos(): array
    {
        return [
            self::DRAFT => 1,
            self::VERIFIKASI_FA => 2,
            self::VERIFIKASI_AP => 3,
            self::KIRIM_JAKARTA => 4,
            self::SIAP_BAYAR => 5,
            self::SELESAI => 0,
            self::REVISI => null,
        ];
    }

    public static function getDescription(mixed $value): string
    {
        $master = static::master();
        if ($master !== null && isset($master[$value])) {
            return $master[$value]->voucher_status_nama;
        }

        return static::fallbackNames()[$value] ?? parent::getDescription($value);
    }

    /** Nomor langkah berdampingan dengan status; 0 = finish; revisi = null (cabang). */
    public static function stepNo(mixed $value): ?int
    {
        $master = static::master();
        if ($master !== null && isset($master[$value])) {
            return $master[$value]->voucher_status_no;
        }

        return static::fallbackNos()[$value] ?? null;
    }

    /** 1..N untuk langkah maju (untuk kompat lama); finish/revisi = 0. */
    public static function stepOf(mixed $value): int
    {
        return static::stepNo($value) ?? 0;
    }

    /** Alur utama bernomor dari master (no > 0 terurut) + finish (no = 0) paling akhir. */
    public static function steps(): array
    {
        $master = static::master();
        if ($master === null) {
            return [self::DRAFT, self::VERIFIKASI_FA, self::VERIFIKASI_AP, self::KIRIM_JAKARTA, self::SIAP_BAYAR, self::SELESAI];
        }

        $codes = VoucherStatus::orderedSteps()->pluck('voucher_status_kode')->all();
        $finish = VoucherStatus::finishRow();
        if ($finish) {
            $codes[] = $finish->voucher_status_kode;
        }

        return $codes !== [] ? $codes : [self::DRAFT, self::VERIFIKASI_FA, self::VERIFIKASI_AP, self::KIRIM_JAKARTA, self::SIAP_BAYAR, self::SELESAI];
    }

    protected static function nextByNo(string $current): ?string
    {
        $master = static::master();
        if ($master === null || ! isset($master[$current])) {
            return null;
        }

        $curNo = $master[$current]->voucher_status_no;
        if ($curNo === null) {
            return null;
        }

        if ($curNo === 0) {
            return null; // finish: tidak ada langkah lagi
        }

        $next = VoucherStatus::whereNotNull('voucher_status_no')
            ->where('voucher_status_no', '>', $curNo)
            ->orderBy('voucher_status_no')
            ->first();

        if ($next) {
            return $next->voucher_status_kode;
        }

        return VoucherStatus::finishRow()?->voucher_status_kode;
    }

    public static function nextOptions(string $current): array
    {
        $master = static::master();
        if ($master !== null) {
            if ($current === self::SELESAI) {
                return [];
            }
            if ($current === self::REVISI) {
                return [self::VERIFIKASI_FA];
            }
            $next = static::nextByNo($current);
            $opts = $next ? [$next] : [];
            $opts[] = self::REVISI;

            return $opts;
        }

        return match ($current) {
            self::DRAFT => [self::VERIFIKASI_FA, self::REVISI],
            self::VERIFIKASI_FA => [self::VERIFIKASI_AP, self::REVISI],
            self::VERIFIKASI_AP => [self::KIRIM_JAKARTA, self::REVISI],
            self::KIRIM_JAKARTA => [self::SIAP_BAYAR, self::REVISI],
            self::SIAP_BAYAR => [self::SELESAI, self::REVISI],
            self::REVISI => [self::VERIFIKASI_FA],
            default => [self::VERIFIKASI_FA, self::REVISI],
        };
    }

    /** Target tombol Setujui: langkah maju pertama (bukan revisi). */
    public static function approveTarget(string $current): ?string
    {
        if ($current === static::SELESAI) {
            return null;
        }

        foreach (static::nextOptions($current) as $opt) {
            if ($opt !== static::REVISI) {
                return $opt;
            }
        }

        return null;
    }
}
