<?php

namespace App\Services;

/**
 * Konversi koordinat UTM (default zona 49S, datum WGS84 — wilayah Kalteng)
 * ke lat/lng, plus normalisasi GeoJSON polygon yang ditempel user:
 * - FeatureCollection / MultiPolygon / UTM mentah → MultiPolygon lat/lng.
 * - Tiap area dipertahankan terpisah (tidak digabung jadi 1 hull).
 * - 1 area → Polygon tunggal (kompatibel data lama); >1 area → MultiPolygon.
 * - Hasil disederhanakan agar muat di kolom text (maks 50.000 char).
 */
class UtmConverter
{
    public static function utmToLatLng(float $e, float $n, int $zone = 49, bool $south = true): array
    {
        $a = 6378137.0; $f = 1 / 298.257223563;
        $k0 = 0.9996; $e2 = $f * (2 - $f); $ep2 = $e2 / (1 - $e2);
        $x = $e - 500000.0; $y = $south ? $n - 10000000.0 : $n;
        $m = $y / $k0;
        $mu = $m / ($a * (1 - $e2 / 4 - 3 * $e2 * $e2 / 64 - 5 * $e2 * $e2 * $e2 / 256));
        $e1 = (1 - sqrt(1 - $e2)) / (1 + sqrt(1 - $e2));
        $j1 = 3 * $e1 / 2 - 27 * $e1 * $e1 * $e1 / 32;
        $j2 = 21 * $e1 * $e1 / 16 - 55 * $e1 * $e1 * $e1 * $e1 / 32;
        $j3 = 151 * $e1 * $e1 * $e1 / 96;
        $j4 = 1097 * $e1 * $e1 * $e1 * $e1 / 512;
        $fp = $mu + $j1 * sin(2 * $mu) + $j2 * sin(4 * $mu) + $j3 * sin(6 * $mu) + $j4 * sin(8 * $mu);
        $c1 = $ep2 * pow(cos($fp), 2);
        $t1 = pow(tan($fp), 2);
        $n1 = $a / sqrt(1 - $e2 * pow(sin($fp), 2));
        $r1 = $a * (1 - $e2) / pow(1 - $e2 * pow(sin($fp), 2), 1.5);
        $d = $x / ($n1 * $k0);
        $lat = $fp - ($n1 * tan($fp) / $r1) * ($d * $d / 2 - (5 + 3 * $t1 + 10 * $c1 - 4 * $c1 * $c1 - 9 * $ep2) * $d * $d * $d * $d / 24 + (61 + 90 * $t1 + 298 * $c1 + 45 * $t1 * $t1 - 252 * $ep2 - 3 * $c1 * $c1) * $d ** 6 / 720);
        $lat = rad2deg($lat);
        $lon0 = ($zone - 1) * 6 - 180 + 3;
        $lon = ($d - (1 + 2 * $t1 + $c1) * $d * $d * $d / 6 + (5 - 2 * $c1 + 28 * $t1 - 3 * $c1 * $c1 + 8 * $ep2 + 24 * $t1 * $t1) * $d ** 5 / 120) / cos($fp);
        $lon = $lon0 + rad2deg($lon);

        return [$lat, $lon];
    }

    /**
     * Normalisasi string GeoJSON mentah (hasil tempel/export QGIS) menjadi
     * Polygon/MultiPolygon dalam lat/lng. QGIS: klik kanan layer → Export →
     * Save Features As → format GeoJSON, CRS EPSG:4326 (atau biarkan UTM 49S,
     * otomatis dikonversi). 1 area → Polygon; >1 area → MultiPolygon.
     * Mengembalikan null bila input bukan JSON polygon yang dikenali.
     */
    public static function normalizePolygon(?string $raw, int $maxChars = 48000): ?string
    {
        $rings = self::extractRings($raw);
        if ($rings === []) {
            return null;
        }

        // Deteksi UTM: koordinat easting/northing (ratusan ribu / jutaan).
        $sample = $rings[0][0];
        $isUtm = abs($sample[0]) > 180 || abs($sample[1]) > 90;
        $clean = [];
        foreach ($rings as $ring) {
            $pts = [];
            foreach ($ring as $c) {
                if (! is_array($c) || count($c) < 2 || ! is_numeric($c[0]) || ! is_numeric($c[1])) {
                    continue;
                }
                if ($isUtm) {
                    [$lat, $lon] = self::utmToLatLng((float) $c[0], (float) $c[1]);
                    $pts[] = [round($lon, 6), round($lat, 6)];
                } else {
                    $pts[] = [round((float) $c[0], 6), round((float) $c[1], 6)];
                }
            }
            // Buang titik duplikat berurutan, tutup ring.
            $pts = array_values($pts);
            if (count($pts) >= 3) {
                $clean[] = self::close($pts);
            }
        }
        if ($clean === []) {
            return null;
        }

        // Sederhanakan per ring sampai total muat (alokasi proporsional, 1 pass per iterasi).
        [$clean, $geo] = self::fitBudget($clean, $maxChars);

        return strlen($geo) <= $maxChars ? $geo : null;
    }

    /** Kecilkan ring secara proporsional agar total char muat budget (target 90% agar konvergen). */
    private static function fitBudget(array $rings, int $maxChars): array
    {
        $geo = self::encodeRings($rings);
        for ($pass = 0; $pass < 8 && strlen($geo) > $maxChars; $pass++) {
            $ratio = ($maxChars * 0.9) / strlen($geo);
            $shrunk = false;
            foreach ($rings as $i => $ring) {
                // Minimal segitiga (3 unik + penutup).
                $keep = max(3, (int) floor(count($ring) * $ratio));
                if ($keep < count($ring)) {
                    $rings[$i] = self::stride($ring, $keep);
                    $shrunk = true;
                }
            }
            if (! $shrunk) {
                break;
            }
            $geo = self::encodeRings($rings);
        }

        return [$rings, $geo];
    }

    /** Ambil $keep titik tersebar merata dari ring, lalu tutup. */
    private static function stride(array $ring, int $keep): array
    {
        // Buang titik penutup duplikat dulu agar sampling merata.
        if (count($ring) > 1 && $ring[0] === end($ring)) {
            array_pop($ring);
        }
        $n = count($ring);
        if ($keep >= $n) {
            return self::close($ring);
        }
        $out = [];
        for ($k = 0; $k < $keep; $k++) {
            $out[] = $ring[(int) floor($k * $n / $keep)];
        }

        return self::close($out);
    }

    /** Kumpulkan semua ring luar ([lng, lat]) dari Polygon/MultiPolygon/Feature/FeatureCollection. */
    public static function extractRings(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $gj = json_decode(trim($raw), true);
        if (! is_array($gj) || ! isset($gj['type'])) {
            return [];
        }
        $rings = [];
        $fromCoords = function ($type, $coords) use (&$rings) {
            if ($type === 'Polygon') {
                if (isset($coords[0]) && is_array($coords[0])) {
                    $rings[] = $coords[0];
                }
            } elseif ($type === 'MultiPolygon') {
                foreach ($coords ?? [] as $poly) {
                    if (isset($poly[0]) && is_array($poly[0])) {
                        $rings[] = $poly[0];
                    }
                }
            }
        };
        match ($gj['type']) {
            'Polygon', 'MultiPolygon' => $fromCoords($gj['type'], $gj['coordinates'] ?? null),
            'Feature' => $fromCoords($gj['geometry']['type'] ?? '', $gj['geometry']['coordinates'] ?? null),
            'FeatureCollection' => (function () use ($gj, $fromCoords) {
                foreach ($gj['features'] ?? [] as $f) {
                    $fromCoords($f['geometry']['type'] ?? '', $f['geometry']['coordinates'] ?? null);
                }
            })(),
            default => null,
        };

        return array_values(array_filter($rings, fn ($r) => is_array($r) && count($r) >= 3));
    }

    /** Encode ring luar: 1 ring → Polygon, >1 ring → MultiPolygon. */
    public static function encodeRings(array $rings): string
    {
        if (count($rings) === 1) {
            return json_encode(['type' => 'Polygon', 'coordinates' => [$rings[0]]]);
        }

        return json_encode(['type' => 'MultiPolygon', 'coordinates' => array_map(fn ($r) => [$r], $rings)]);
    }

    /**
     * Versi ringan polygon untuk TAMPILAN peta (bukan verifikasi).
     * Semua area tetap ada, hanya titiknya dijarangkan agar halaman ringan.
     */
    public static function displayPolygon(?string $raw, int $maxChars = 60000): ?string
    {
        $rings = self::extractRings($raw);
        if ($rings === []) {
            return null;
        }
        [$rings, $geo] = self::fitBudget($rings, $maxChars);

        return strlen($geo) <= $maxChars ? $geo : null;
    }

    /** Convex hull (Andrew monotone chain), input [lng, lat]. */
    private static function convexHull(array $pts): array
    {
        usort($pts, fn ($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);
        $pts = array_values(array_unique($pts, SORT_REGULAR));
        $cross = fn ($o, $a, $b) => ($a[0] - $o[0]) * ($b[1] - $o[1]) - ($a[1] - $o[1]) * ($b[0] - $o[0]);
        $lower = [];
        foreach ($pts as $p) {
            while (count($lower) >= 2 && $cross($lower[count($lower) - 2], $lower[count($lower) - 1], $p) <= 0) {
                array_pop($lower);
            }
            $lower[] = $p;
        }
        $upper = [];
        foreach (array_reverse($pts) as $p) {
            while (count($upper) >= 2 && $cross($upper[count($upper) - 2], $upper[count($upper) - 1], $p) <= 0) {
                array_pop($upper);
            }
            $upper[] = $p;
        }
        array_pop($lower);
        array_pop($upper);

        return array_merge($lower, $upper);
    }

    private static function close(array $ring): array
    {
        if ($ring === []) {
            return $ring;
        }
        if ($ring[0] !== end($ring)) {
            $ring[] = $ring[0];
        }

        return $ring;
    }

    /** Titik tengah (rata-rata semua ring luar) polygon GeoJSON — untuk isi lat/lng otomatis. */
    public static function centroidOfPolygon(?string $polygonJson): ?array
    {
        $rings = self::extractRings($polygonJson);
        if ($rings === []) {
            return null;
        }
        $sumLat = 0.0;
        $sumLng = 0.0;
        $n = 0;
        foreach ($rings as $ring) {
            foreach ($ring as $c) {
                if (! is_array($c) || ! isset($c[0], $c[1]) || ! is_numeric($c[0]) || ! is_numeric($c[1])) {
                    continue;
                }
                $sumLng += (float) $c[0];
                $sumLat += (float) $c[1];
                $n++;
            }
        }
        if ($n === 0) {
            return null;
        }

        return [$sumLat / $n, $sumLng / $n]; // [lat, lng]
    }
}
