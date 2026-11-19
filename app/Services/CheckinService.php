<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * Check-in signing: gambar frame (tanggal+alamat+koordinat) di-overlay
 * dengan GD, lalu hash + metadata geo di-RSA-sign seperti signFile.
 * Format embed SAMA (MARKER DIGITALSIGV1) sehingga ValidateController
 * yang sudah ada bisa memvalidasi foto check-in tanpa perubahan.
 */
class CheckinService
{
    public function __construct(
        private CryptoService $crypto,
        private FileSigningService $fileSigning,
    ) {}

    /**
     * @param string $jpegBytes  bytes foto asli dari HP
     * @param bool $preframed    true = template sudah ditempel di HP, server tidak membingkai lagi
     * @return array{bytes, content_hash, embedded_signature, expired_at}
     */
    public function signCheckin(string $jpegBytes, User $user, float $lat, float $lng, ?float $accuracy, ?string $address, ?string $clientTimestamp, bool $front = false, bool $preframed = false): array
    {
        $framed = $preframed ? $jpegBytes : $this->applyFrame($jpegBytes, $lat, $lng, $address, $front);
        $contentHash = hash('sha256', $framed);

        $metadata = [
            'hash' => $contentHash,
            'userId' => $user->id,
            'timestamp' => now()->toIso8601String(),
            'clientTimestamp' => $clientTimestamp,
            'expiredAt' => null,
            'label' => 'checkin',
            'keyFingerprint' => $user->activeCryptoKey->fingerprint ?? null,
            // Data anti-palsu: lokasi ikut di-sign, tidak bisa diubah tanpa merusak signature
            'geo' => [
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $accuracy,
                'address' => $address,
            ],
        ];

        $canonical = $this->crypto->buildPayload($metadata);
        $sign = $this->crypto->sign($user, $canonical);

        $embedded = json_encode([
            'metadata' => $metadata,
            'signature' => $sign['signature'],
        ], JSON_UNESCAPED_SLASHES);

        return [
            'bytes' => $framed.FileSigningService::MARKER.base64_encode($embedded),
            'content_hash' => $contentHash,
            'embedded_signature' => $embedded,
            'expired_at' => null,
        ];
    }

    /**
     * Putar gambar sesuai tag EXIF Orientation (1=normal, 3=180, 6=-90, 8=90).
     * GD mengabaikan EXIF, tanpa ini foto kamera belakang tampil miring.
     */
    private function normalizeOrientation($img, string $rawBytes)
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'chk');
        file_put_contents($tmp, $rawBytes);
        $exif = @exif_read_data($tmp);
        @unlink($tmp);

        $deg = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($deg !== 0) {
            $rotated = imagerotate($img, $deg, 0);
            if ($rotated !== false) {
                imagedestroy($img);
                return $rotated;
            }
        }

        return $img;
    }

    /**
     * Gambar banner bawah font besar + logo APN kiri-atas.
     * Font TrueType (Arial) bila tersedia, fallback font built-in GD.
     */
    private function applyFrame(string $jpegBytes, float $lat, float $lng, ?string $address, bool $front = false): string
    {
        $img = @imagecreatefromstring($jpegBytes);
        if ($img === false) {
            throw new RuntimeException('Foto tidak valid (bukan JPEG/PNG)');
        }

        // Jaring pengaman orientasi: putar sesuai EXIF (kamera belakang sering landscape).
        $img = $this->normalizeOrientation($img, $jpegBytes);

        // Kamera depan: mirror horizontal agar sama seperti preview di HP.
        if ($front && function_exists('imageflip')) {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // --- Logo APN kiri-atas (lebar ~28% foto) ---
        $this->overlayLogo($img, $w);

        // --- Banner bawah: tanggal BESAR, alamat+koordinat lebih kecil (wrap, tidak kepotong) ---
        $dateSize = max(20, (int) ($w / 26));
        $smallSize = max(13, (int) ($dateSize * 0.6));
        $pad = (int) ($dateSize * 0.8);

        $ttf = $this->fontPath();
        $date = now()->format('d M Y H:i:s');
        $coord = sprintf('%.6f, %.6f', $lat, $lng);
        $addrLines = $this->wrapText($address ?: '-', $ttf, $smallSize, $w - $pad * 2, 2);

        $dateH = $ttf ? (int) ($dateSize * 1.5) : imagefontheight(5) + 8;
        $smallH = $ttf ? (int) ($smallSize * 1.45) : imagefontheight(5) + 8;
        $barH = $pad * 2 + $dateH + $smallH * (count($addrLines) + 1);

        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, $h - $barH, $w, $h, $black);

        $white = imagecolorallocate($img, 255, 255, 255);
        $yellow = imagecolorallocate($img, 255, 213, 79);

        $ttf = $this->fontPath();
        $x = $pad;
        $y = $h - $barH + $pad;

        if ($ttf) {
            imagettftext($img, $dateSize, 0, $x, $y + $dateSize, $yellow, $ttf, $date);
            $y += $dateH;
            foreach ($addrLines as $line) {
                imagettftext($img, $smallSize, 0, $x, $y + $smallSize, $white, $ttf, $line);
                $y += $smallH;
            }
            imagettftext($img, $smallSize, 0, $x, $y + $smallSize, $white, $ttf, $coord);
        } else {
            $font = 5;
            imagestring($img, $font, $x, $y, $date, $yellow);
            $y += $dateH;
            foreach (array_merge($addrLines, [$coord]) as $line) {
                imagestring($img, $font, $x, $y, mb_substr($line, 0, 90), $white);
                $y += $smallH;
            }
        }

        ob_start();
        imagejpeg($img, null, 90);
        $out = ob_get_clean();
        imagedestroy($img);

        return $out;
    }

    /**
     * Bungkus teks agar muat dalam $maxWidth px (maks $maxLines baris, sisanya …).
     */
    private function wrapText(string $text, ?string $ttf, int $size, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [$text];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $try = $current === '' ? $word : $current.' '.$word;
            if ($ttf && $this->textWidth($try, $ttf, $size) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
                if (count($lines) >= $maxLines) {
                    break;
                }
            } else {
                $current = $try;
            }
        }
        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        // Sisa kata yang kepotong -> tempel … di baris terakhir
        $used = array_sum(array_map(fn ($l) => str_word_count($l), $lines));
        $total = str_word_count($text);
        if ($used < $total && count($lines)) {
            $last = count($lines) - 1;
            $lines[$last] = rtrim($lines[$last]).'…';
        }

        return $lines ?: ['-'];
    }

    private function textWidth(string $text, string $ttf, int $size): int
    {
        $box = imagettfbbox($size, 0, $ttf, $text);

        return (int) (max($box[2], $box[4]) - min($box[0], $box[6]));
    }

    private function fontPath(): ?string
    {
        foreach (['C:\Windows\Fonts\arialbd.ttf', 'C:\Windows\Fonts\arial.ttf'] as $p) {
            if (is_file($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Tempel logo public/apn.png kiri-atas (lebar ~28% foto).
     */
    private function overlayLogo($img, int $photoW): void
    {
        $logoPath = public_path('apn.png');
        if (! is_file($logoPath)) {
            return;
        }
        $logo = @imagecreatefrompng($logoPath);
        if ($logo === false) {
            return;
        }

        $targetW = (int) ($photoW * 0.28);
        $scale = $targetW / imagesx($logo);
        $targetH = (int) (imagesy($logo) * $scale);

        $resized = imagescale($logo, $targetW, $targetH);
        imagedestroy($logo);
        if ($resized === false) {
            return;
        }

        $margin = (int) ($photoW * 0.03);
        imagecopy($img, $resized, $margin, $margin, 0, 0, $targetW, $targetH);
        imagedestroy($resized);
    }
}
