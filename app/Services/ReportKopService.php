<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebsiteSetting;

/**
 * Menyusun kop, judul filter, dan footer untuk halaman cetak/PDF report.
 * Port dari AMS (ReportKopService) — disesuaikan ke filter kalteng2.
 *
 * Cara pakai di controller:
 *   $kop = ReportKopService::make('REPORT ABSEN', $request->only(['from','to','user_id','status','jenis']));
 *   return view('pages.checkinreport.print', array_merge(['data' => $data, ...], $kop));
 *
 * $kop berisi: kop, judulReport, filterTitle, footer, periodeText.
 */
class ReportKopService
{
    /**
     * @param  string  $judul  Judul utama report, mis. "REPORT ABSEN".
     * @param  array  $filter  Filter aktif: from/to (atau dari/sampai),
     *                         user_id, status, jenis, kebun_id, dsb.
     */
    public static function make(string $judul, array $filter = []): array
    {
        $report = WebsiteSetting::report();
        $parts = [];

        $dari = $filter['dari'] ?? $filter['from'] ?? null;
        $sampai = $filter['sampai'] ?? $filter['to'] ?? null;
        if (! empty($dari) || ! empty($sampai)) {
            $parts[] = 'Periode: '.(! empty($dari) ? formatDate($dari) : '-')
                .' s/d '.(! empty($sampai) ? formatDate($sampai) : '-');
        }

        if (! empty($filter['user_id'])) {
            $user = User::find($filter['user_id']);
            $parts[] = 'User: '.($user?->name ?? $filter['user_id']);
        }

        foreach (['status' => 'Status', 'jenis' => 'Jenis', 'kebun' => 'Kebun'] as $key => $label) {
            if (! empty($filter[$key])) {
                $parts[] = "{$label}: {$filter[$key]}";
            }
        }

        $footer = $report;
        if (! empty($filter['kota'] ?? null)) {
            $footer['kota'] = $filter['kota'];
        }

        return [
            'kop' => $report,
            'judulReport' => $judul,
            'filterTitle' => implode(' • ', $parts) ?: 'Semua Data',
            'footer' => $footer,
            'periodeText' => implode(' • ', $parts),
        ];
    }

    /** Logo kop sebagai data-URI (aman untuk DomPDF) atau null. */
    public static function logoDataUri(): ?string
    {
        return static::imageDataUri(config('website.logo'));
    }

    /**
     * Gambar report (header kiri/kanan, footer) sebagai data-URI (aman untuk DomPDF).
     * Terima path config seperti "storage/website/xxx.png", "/storage/...", atau URL.
     */
    public static function imageDataUri(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $file = public_path(ltrim(preg_replace('#^/?storage/#', '', $path), '/'));
        if (str_starts_with($path, 'storage/') || str_starts_with($path, '/storage/')) {
            $file = public_path($path);
            if (! is_file($file)) {
                $file = public_path(ltrim($path, '/'));
            }
        }
        if (! is_file($file)) {
            return null;
        }
        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }

    /** Data-URI gambar kiri, kanan, footer untuk view PDF. */
    public static function pdfImages(): array
    {
        $report = WebsiteSetting::report();

        return [
            'kopImgLeft' => static::imageDataUri($report['header_left'] ?? null)
                ?? static::logoDataUri(),
            'kopImgRight' => static::imageDataUri($report['header_image'] ?? null),
            'kopImgFooter' => static::imageDataUri($report['footer_image'] ?? null),
        ];
    }
}
