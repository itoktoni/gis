<?php

namespace App\Services;

/**
 * Konversi koordinat UTM (default zona 49S, datum WGS84 — wilayah Kalteng)
 * ke lat/lng, plus normalisasi GeoJSON polygon yang ditempel user:
 * - FeatureCollection / MultiPolygon / UTM mentah → Polygon lat/lng tunggal.
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
     * '{"type":"Polygon","coordinates":[...]}' dalam lat/lng.
     * Mengembalikan null bila input bukan JSON polygon yang dikenali.
     */
    public static function normalizePolygon(?string $raw, int $maxChars = 48000): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $gj = json_decode(trim($raw), true);
        if (! is_array($gj) || ! isset($gj['type'])) {
            return null;
        }

        // Kumpulkan semua ring luar sebagai [lng, lat].
        $rings = [];
        $collect = function ($coords) use (&$rings) {
            foreach ($coords as $poly) {
                if (is_array($poly) && isset($poly[0]) && is_array($poly[0]) && isset($poly[0][0]) && is_numeric($poly[0][0])) {
                    $rings[] = $poly;
                }
            }
        };
        match ($gj['type']) {
            'Polygon' => $collect([$gj['coordinates'][0] ?? []]),
            'MultiPolygon' => $collect(array_map(fn ($p) => $p[0] ?? [], $gj['coordinates'] ?? [])),
            'Feature' => self::collectGeometry($gj['geometry'] ?? [], $rings),
            'FeatureCollection' => (function () use ($gj, &$rings) {
                foreach ($gj['features'] ?? [] as $f) {
                    self::collectGeometry($f['geometry'] ?? [], $rings);
                }
            })(),
            default => null,
        };
        if ($rings === []) {
            return null;
        }

        // Deteksi UTM: koordinat easting/northing (ratusan ribu / jutaan).
        $sample = $rings[0][0];
        $isUtm = abs($sample[0]) > 180 || abs($sample[1]) > 90;
        $pts = [];
        foreach ($rings as $ring) {
            foreach ($ring as $c) {
                if (! is_array($c) || count($c) < 2 || ! is_numeric($c[0]) || ! is_numeric($c[1])) {
                    continue;
                }
                if ($isUtm) {
                    [$lat, $lon] = self::utmToLatLng((float) $c[0], (float) $c[1]);
                    $pts[] = [$lon, $lat];
                } else {
                    $pts[] = [(float) $c[0], (float) $c[1]];
                }
            }
        }
        if (count($pts) < 3) {
            return null;
        }

        // Sederhanakan: convex hull bila cincin banyak, lalu downsample.
        $hull = self::convexHull($pts);
        if (count($hull) < count($pts) / 4 && count($pts) > 500) {
            $pts = $hull;
        }
        $geo = json_encode(['type' => 'Polygon', 'coordinates' => [self::close($pts)]]);
        if (strlen($geo) > $maxChars) {
            // Ambil hull + downsample sampai muat.
            $pts = $hull;
            while (count($pts) > 8) {
                $geo = json_encode(['type' => 'Polygon', 'coordinates' => [self::close($pts)]]);
                if (strlen($geo) <= $maxChars) {
                    break;
                }
                $pts = array_values(array_filter($pts, fn ($_, $i) => $i % 2 === 0, ARRAY_FILTER_USE_BOTH));
            }
            $geo = json_encode(['type' => 'Polygon', 'coordinates' => [self::close($pts)]]);
        }

        return strlen($geo) <= $maxChars ? $geo : null;
    }

    private static function collectGeometry(array $geom, array &$rings): void
    {
        if (($geom['type'] ?? '') === 'Polygon') {
            $rings[] = $geom['coordinates'][0] ?? [];
        } elseif (($geom['type'] ?? '') === 'MultiPolygon') {
            foreach ($geom['coordinates'] ?? [] as $p) {
                $rings[] = $p[0] ?? [];
            }
        }
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
}
