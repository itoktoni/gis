<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Kunjungan;
use App\Models\Signature;
use App\Services\CheckinService;
use App\Services\CryptoService;
use App\Services\ValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function __construct(
        private CheckinService $checkinService,
        private ValidationService $validation,
        private CryptoService $crypto,
    ) {}

    /**
     * Secret HMAC khusus aplikasi check-in (diputar per permintaan).
     * GET /api/absensi/secret (auth:sanctum)
     */
    public function secret(Request $request): JsonResponse
    {
        $user = $request->user();
        if (empty($user->checkin_secret)) {
            $user->checkin_secret = bin2hex(random_bytes(32));
            $user->save();
        }
        if (! $user->activeCryptoKey) {
            $this->crypto->generateKeyPairForUser($user);
        }

        return response()->json(['checkin_secret' => $user->checkin_secret]);
    }

    /**
     * Statistik check-in milik user per rentang tanggal untuk dashboard HP.
     * GET /api/absensi/stats?from=YYYY-MM-DD&to=YYYY-MM-DD (auth:sanctum)
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);

        $q = Checkin::where('user_id', $request->user()->id)
            ->when($request->filled('from'), fn ($qq) => $qq->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($qq) => $qq->whereDate('created_at', '<=', $request->input('to')));

        $rows = (clone $q)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return response()->json([
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'total' => (int) array_sum($rows->all()),
            'valid' => (int) ($rows['valid'] ?? 0),
            'invalid' => (int) ($rows['invalid'] ?? 0),
            'fake' => (int) ($rows['fake'] ?? 0),
        ]);
    }

    /**
     * Config pengingat check-in untuk notifikasi lokal Android.
     * GET /api/absensi/reminder-config (auth:sanctum)
     * HP memuat ini setiap aplikasi dibuka lalu menjadwalkan alarm harian.
     */
    public function reminderConfig(): JsonResponse
    {
        $cfg = config('absensi.reminder', []);
        $enabled = filter_var($cfg['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $times = [];

        if ($enabled) {
            $toMin = function ($hm) {
                $p = explode(':', (string) $hm);
                return ((int) ($p[0] ?? 0)) * 60 + ((int) ($p[1] ?? 0));
            };
            $startMin = $toMin($cfg['start'] ?? '06:00');
            $endMin = $toMin($cfg['end'] ?? '17:00');
            $intervalMin = max(1, (int) round(((float) ($cfg['interval_hours'] ?? 3)) * 60));
            $max = max(1, min(96, (int) ($cfg['max_per_day'] ?? 8)));
            // Jendela boleh lintas tengah malam (mis. start 18:00 end 06:00):
            // menit berjalan memutar modulo 24 jam.
            $spanMin = $endMin >= $startMin ? ($endMin - $startMin) : ((24 * 60 - $startMin) + $endMin);
            // Total slot yang muat di jendela (tanpa batas max) — untuk deteksi salah konfigurasi.
            $windowSlots = (int) (floor($spanMin / $intervalMin) + 1);
            for ($m = $startMin, $n = 0; ($m - $startMin) % (24 * 60) <= $spanMin && $n < $max; $m += $intervalMin, $n++) {
                $mm = $m % (24 * 60);
                $times[] = sprintf('%02d:%02d', intdiv($mm, 60), $mm % 60);
            }
            $truncated = $windowSlots > count($times);
        }

        return response()->json([
            'enabled' => $enabled,
            'times' => $times,
            'interval_hours' => (float) ($cfg['interval_hours'] ?? 3),
            'min_interval_hours' => (float) config('absensi.min_interval_hours', 3),
            'max_per_day' => $max ?? (int) ($cfg['max_per_day'] ?? 3),
            // Diagnostik salah konfigurasi: true bila jendela masih muat slot lagi
            // tapi dipotong oleh max_per_day (mis. interval 0.1 jam + max 3
            // hanya menghasilkan 06:00, 06:06, 06:12 lalu diam seharian).
            'truncated_by_max' => $truncated ?? false,
            'total_slots_in_window' => $windowSlots ?? 0,
            'start' => $cfg['start'] ?? '06:00',
            'end' => $cfg['end'] ?? '17:00',
            'title' => $cfg['title'] ?? 'Waktunya Check-In',
            'message' => $cfg['message'] ?? 'Jangan lupa check-in kehadiran hari ini.',
        ]);
    }

    /**
     * Titik kunjungan milik user. GET /api/absensi/kunjungan?date=YYYY-MM-DD
     */
    public function kunjungan(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $allowed = $request->user()->allowedKebunIds();
        $rows = Kunjungan::with(['hasObjek', 'hasKebun'])
            ->where(function ($q) use ($request) {
                // Pemilik (kolom lama) ATAU anggota pivot kunjungan_user.
                $q->where('kunjungan_id_user', $request->user()->id)
                    ->orWhereHas('hasUsers', fn ($qq) => $qq->where('users.id', $request->user()->id));
            })
            ->where(function ($q) use ($date) {
                // Tanpa tanggal = berlaku selamanya, selalu ikut
                $q->whereDate('kunjungan_tanggal', $date)->orWhereNull('kunjungan_tanggal');
            })
            ->when($allowed !== null, fn ($q) => $q->where(function ($w) use ($allowed) {
                $w->whereNull('kunjungan_id_kebun')->orWhereIn('kunjungan_id_kebun', $allowed);
            }))
            ->orderBy('kunjungan_nama')->get()
            ->map(fn ($k) => [
                'id' => $k->kunjungan_id,
                'name' => $k->kunjungan_nama,
                'latitude' => (float) $k->kunjungan_lat,
                'longitude' => (float) $k->kunjungan_lng,
                'radius_m' => (int) $k->kunjungan_radius_m,
                'polygon' => $k->kunjungan_polygon,
                'status' => $k->kunjungan_status ?? 'pending',
                'objek' => $k->hasObjek?->objek_nama,
                'kebun' => $k->hasKebun?->kebun_nama,
                'date' => $k->kunjungan_tanggal?->format('Y-m-d'),
            ]);

        return response()->json(['date' => $date, 'data' => $rows]);
    }

    /**
     * 1 tombol: HP foto + HMAC-sign(payload) -> server verifikasi -> simpan.
     * POST /api/absensi/checkin (auth:sanctum)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => 'required|file|mimes:png,jpg,jpeg|max:10240',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:100000',
            'address' => 'nullable|string|max:500',
            'client_timestamp' => 'required|date',
            'payload' => 'required|string',
            'hmac' => 'required|string|size:64',
            'kunjungan_id' => 'nullable|string|exists:kunjungan,kunjungan_id',
        ]);

        $user = $request->user();
        $failures = [];
        $rangeFailures = [];

        // Proteksi jeda minimal antar check-in DINONAKTIFKAN: user boleh absen
        // berkali-kali (mis. salah foto / salah titik, bisa langsung ulangi).
        // Setiap kiriman tetap diverifikasi penuh dan tercatat sebagai baris baru.

        // --- Verifikasi titik kunjungan: polygon bila ada, else radius titik ---
        $kunjungan = null;
        $selisihRadius = null;
        $jarakM = null;
        if (! empty($validated['kunjungan_id'])) {
            $kunjungan = Kunjungan::find($validated['kunjungan_id']);
            if (! $kunjungan || ! in_array((int) $user->id, $kunjungan->assignedUserIds(), true)) {
                return response()->json(['message' => 'Titik kunjungan tidak valid'], 422);
            }
            // Polygon per titik (GeoJSON): gantikan radius bila terisi.
            if (! empty($kunjungan->kunjungan_polygon)) {
                $tol = (float) (config('absensi.polygon_tolerance_m', 100));
                $dPoly = self::distToPolygonM(
                    (float) $validated['latitude'], (float) $validated['longitude'], (string) $kunjungan->kunjungan_polygon);
                if ($dPoly === null) {
                    $msg = sprintf('Polygon titik "%s" rusak — hubungi admin', $kunjungan->kunjungan_nama);
                    $failures[] = $msg;
                    $rangeFailures[] = $msg;
                } elseif ($dPoly > $tol) {
                    $msg = sprintf('Di luar area "%s" (%.0f m dari batas)', $kunjungan->kunjungan_nama, $dPoly);
                    $failures[] = $msg;
                    $rangeFailures[] = $msg;
                }
                $jarakM = round($dPoly ?? -1, 1);
                $selisihRadius = round(($dPoly ?? 0) - $tol, 1);
            } else {
                $dist = self::distanceM(
                    (float) $kunjungan->kunjungan_lat, (float) $kunjungan->kunjungan_lng,
                    (float) $validated['latitude'], (float) $validated['longitude']);
                $jarakM = round($dist, 1);
                $selisihRadius = round($dist - (float) $kunjungan->kunjungan_radius_m, 1);
                if ($dist > (float) $kunjungan->kunjungan_radius_m) {
                    $msg = sprintf('Di luar titik "%s" (%.0f m, batas %d m)', $kunjungan->kunjungan_nama, $dist, $kunjungan->kunjungan_radius_m);
                    $failures[] = $msg;
                    $rangeFailures[] = $msg;
                }
            }
        }

        // --- Verifikasi polygon kebun (dengan toleransi meter dari tepi) ---
        // Cakupan: kebun milik titik kunjungan yang dipilih.
        if ($kunjungan && ! empty($kunjungan->kunjungan_id_kebun)) {
            $kebun = \App\Models\Kebun::find($kunjungan->kunjungan_id_kebun);
            if ($kebun && ! empty($kebun->kebun_polygon)) {
                $tol = (float) (config('absensi.polygon_tolerance_m', 100));
                $edge = self::distToPolygonM(
                    (float) $validated['latitude'], (float) $validated['longitude'], (string) $kebun->kebun_polygon
                );
                if ($edge === null) {
                    $failures[] = sprintf('Polygon kebun "%s" rusak/tidak terbaca', $kebun->kebun_nama ?? '-');
                } elseif ($edge > $tol) {
                    $msg = sprintf(
                        'Di luar area kebun "%s" (±%.0f m dari batas, toleransi %d m)',
                        $kebun->kebun_nama ?? '-', $edge, (int) $tol
                    );
                    $failures[] = $msg;
                    $rangeFailures[] = $msg;
                }
            }
        }

        // Akurasi buruk TIDAK menggagalkan total (foto tetap bukti): dicatat sebagai
        // failure agar status tidak VALID, tapi check-in tetap tersimpan.
        if (isset($validated['accuracy']) && (float) $validated['accuracy'] > (float) config('absensi.max_accuracy_m', 100)) {
            $msg = sprintf('Akurasi GPS buruk (±%.0f m, batas %d m) — pindah ke area terbuka bila berulang', (float) $validated['accuracy'], (int) config('absensi.max_accuracy_m', 100));
            $failures[] = $msg;
            $rangeFailures[] = $msg;
        }

        $payload = json_decode($validated['payload'], true);
        $isOffline = ! empty($payload['offline']);
        $window = $isOffline ? 7 * 24 * 3600 : 300;

        $diff = abs(now()->diffInSeconds(\Carbon\Carbon::parse($validated['client_timestamp'])));
        if ($diff > $window) {
            $failures[] = 'Waktu HP tidak sesuai server (selisih > '.($isOffline ? '7 hari' : '5 menit').')';
        }

        if (! is_array($payload)
            || (int) ($payload['user_id'] ?? 0) !== (int) $user->id
            || abs(now()->diffInSeconds(\Carbon\Carbon::parse($payload['timestamp'] ?? '2000-01-01'))) > $window
        ) {
            $failures[] = 'Payload check-in tidak valid';
        } elseif (! $user->checkin_secret) {
            $failures[] = 'Secret aplikasi hilang, buka ulang menu absensi';
        } else {
            $expected = hash_hmac('sha256', $validated['payload'], hex2bin($user->checkin_secret));
            if (! hash_equals($expected, strtolower($validated['hmac']))) {
                $failures[] = 'Tanda aplikasi tidak valid (bukan dari aplikasi resmi / data diubah)';
            }
        }

        $bytes = file_get_contents($request->file('photo')->getRealPath());
        if (! hash_equals($payload['image_hash'] ?? '', hash('sha256', $bytes))) {
            $failures[] = 'Foto berubah selama pengiriman';
        }
        if (abs((float) ($payload['lat'] ?? 999) - (float) $validated['latitude']) > 0.0001
            || abs((float) ($payload['lng'] ?? 999) - (float) $validated['longitude']) > 0.0001) {
            $failures[] = 'Koordinat berubah selama pengiriman';
        }

        if (! empty($payload['mock'])) {
            $failures[] = 'GPS palsu (mock location) terdeteksi di HP';
        }

        if (! empty($payload['dev_mode'])) {
            $failures[] = 'Developer options aktif di HP';
        }

        if (! empty($payload['rooted'])) {
            $failures[] = 'Perangkat di-root (risiko fake GPS level sistem)';
        }

        if (! empty($payload['xposed'])) {
            $failures[] = 'Framework Xposed/LSPosed terdeteksi (risiko bypass deteksi mock)';
        }

        // Cek selisih lokasi jaringan vs GPS DINONAKTIFKAN: lastKnown NETWORK_PROVIDER
        // sering berupa cache basi kota lain sehingga menyesatkan (vonis ratusan km).
        // Data net_lat/net_lng tetap direkam di payload untuk perbandingan manual.

        $last = Checkin::where('user_id', $user->id)->latest()->first();
        if ($last && $last->client_timestamp) {
            $km = self::distanceM((float) $last->latitude, (float) $last->longitude, (float) $validated['latitude'], (float) $validated['longitude']) / 1000;
            $hours = max(abs(now()->diffInSeconds($last->created_at)) / 3600, 1 / 3600);
            if ($km / $hours > 250) {
                $failures[] = sprintf('Perpindahan tidak wajar: %.0f km dalam %.0f mnt', $km, $hours * 60);
            }
        }

        $acc = $payload['accuracy'] ?? null;
        $used = $payload['sat_used_in_fix'] ?? null;
        if (is_numeric($acc) && (float) $acc < 20 && $used !== null && (int) $used === 0) {
            $failures[] = 'Akurasi diklaim bagus (<20 m) tapi 0 satelit GNSS terkunci';
        }
        $gpsTime = $payload['gps_time'] ?? null;
        if (is_numeric($gpsTime)) {
            $gpsDiff = abs(now()->diffInSeconds(\Carbon\Carbon::createFromTimestampMs((int) $gpsTime)));
            if ($gpsDiff > 600) {
                $failures[] = 'Jam GPS tidak cocok dengan waktu check-in (>10 menit)';
            }
        }

        $locAge = $payload['loc_age_ms'] ?? null;
        if (is_numeric($locAge) && ((int) $locAge < 0 || (int) $locAge > 60000)) {
            $failures[] = 'Fix lokasi basi/di-replay (>60 detik)';
        }

        if (isset($validated['accuracy']) && (float) $validated['accuracy'] < 5) {
            $recentAcc = Checkin::where('user_id', $user->id)->latest()->limit(3)->pluck('accuracy');
            if ($recentAcc->count() === 3 && $recentAcc->every(fn ($a) => $a !== null && abs((float) $a - (float) $validated['accuracy']) < 0.01)) {
                $failures[] = 'Pola akurasi identik berulang (ciri fake GPS)';
            }
        }

        if (! empty($payload['motion_still']) && $last && $last->client_timestamp) {
            $mKm = self::distanceM((float) $last->latitude, (float) $last->longitude, (float) $validated['latitude'], (float) $validated['longitude']) / 1000;
            $mMin = abs(now()->diffInSeconds($last->created_at)) / 60;
            if ($mKm > 2 && $mMin < 30) {
                $failures[] = sprintf('Berpindah %.0f km dalam %.0f mnt tapi sensor gerak diam — indikasi teleportasi', $mKm, $mMin);
            }
        }

        // Template ditempel di HP: pastikan tulisan koordinat di banner cocok dengan
        // koordinat terverifikasi (toleransi ~11 m). Beda = banner dimanipulasi.
        if (! empty($payload['framed']) && ! empty($payload['banner_coord'])) {
            $parts = preg_split('/[,\s;]+/', trim((string) $payload['banner_coord']));
            $parts = array_values(array_filter($parts, fn ($p) => is_numeric($p)));
            if (count($parts) >= 2) {
                $bLat = (float) $parts[0];
                $bLng = (float) $parts[1];
                if (abs($bLat - (float) $validated['latitude']) > 0.0001
                    || abs($bLng - (float) $validated['longitude']) > 0.0001) {
                    $failures[] = 'Tulisan koordinat di foto tidak cocok dengan lokasi terverifikasi (indikasi manipulasi template)';
                }
            }
        }

        // Check-in BIASA (tanpa titik kunjungan): tanpa validasi — yang penting
        // terkirim. Langsung VALID apa pun kondisi GPS/perangkatnya.
        // Validasi titik tumpu (radius titik + area/polygon kebun + anti-fake)
        // HANYA berlaku bila memakai titik kunjungan (kunjungan_id terisi).
        if (empty($validated['kunjungan_id'])) {
            $failures = [];
            $rangeFailures = [];
        }

        $authentic = empty($failures);
        // Di luar jangkauan (radius/polygon) = INVALID, bukan PALSU.
        // PALSU hanya untuk indikasi pemalsuan (mock, root, Xposed, dsb).
        $rangeOnly = ! $authentic && count($failures) === count($rangeFailures);
        $status = $authentic ? 'valid' : ($rangeOnly ? 'invalid' : 'fake');

        $address = $validated['address'] ?? null;
        if (empty($address)) {
            $address = $this->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']);
        }

        $origLat = $origLng = $origSource = $origAcc = null;
        if (! empty($failures)) {
            $mock = ! empty($payload['mock']);
            if ($mock) {
                // Prioritas dugaan lokasi asli: GPS mentah (paling akurat) -> jaringan -> IP (paling kasar)
                if (isset($payload['gps_raw_lat'], $payload['gps_raw_lng']) && is_numeric($payload['gps_raw_lat'])) {
                    $origLat = (float) $payload['gps_raw_lat'];
                    $origLng = (float) $payload['gps_raw_lng'];
                    $origSource = 'gps_raw';
                    $origAcc = $payload['gps_raw_acc'] ?? null;
                } elseif (isset($payload['net_lat'], $payload['net_lng']) && is_numeric($payload['net_lat'])) {
                    $origLat = (float) $payload['net_lat'];
                    $origLng = (float) $payload['net_lng'];
                    $origSource = 'network';
                    $origAcc = $payload['net_acc'] ?? null;
                } elseif (isset($payload['ip_lat'], $payload['ip_lng']) && is_numeric($payload['ip_lat'])) {
                    $origLat = (float) $payload['ip_lat'];
                    $origLng = (float) $payload['ip_lng'];
                    $origSource = 'ip';
                }
            } else {
                $origLat = (float) $validated['latitude'];
                $origLng = (float) $validated['longitude'];
            }
        }
        $origAddr = ($origLat !== null) ? $this->reverseGeocode($origLat, $origLng) : null;

        $result = $this->checkinService->signCheckin(
            $bytes, $user,
            (float) $validated['latitude'], (float) $validated['longitude'],
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            $address,
            $validated['client_timestamp'] ?? null,
            ! empty($payload['front']),
            ! empty($payload['framed']),
        );

        // Anti-replay: foto yang isinya sama persis sudah pernah dipakai check-in
        // (indikasi unduh lalu unggah ulang sebagai check-in baru).
        // Hanya untuk titik kunjungan; check-in biasa bebas tanpa validasi.
        if (! empty($validated['kunjungan_id']) && Signature::where('content_hash', $result['content_hash'])->exists()) {
            $failures[] = 'Foto sudah pernah digunakan untuk check-in lain (indikasi unduh & unggah ulang)';
            if ($status === 'valid') {
                $status = 'invalid';
                $authentic = false;
            }
        }

        $signature = Signature::create([
            'user_id' => $user->id,
            'crypto_key_id' => $user->activeCryptoKey->id,
            'type' => 'checkin',
            'label' => $authentic ? 'checkin' : ($status === 'invalid' ? 'checkin-invalid' : 'checkin-fake'),
            'original_filename' => $request->file('photo')->getClientOriginalName(),
            'content_hash' => $result['content_hash'],
            'embedded_signature' => $result['embedded_signature'],
            'expired_at' => null,
            'mock' => ! empty($payload['mock']),
            'developer' => ! empty($payload['dev_mode']),
            'alamat' => $address ? mb_substr($address, 0, 500) : null,
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            'jarak_m' => $jarakM,
            'ip_address' => $request->ip(),
            'client_timestamp' => $validated['client_timestamp'] ?? null,
        ]);

        $filename = $signature->id.'.jpg';
        \Illuminate\Support\Facades\Storage::disk('public')->put('checkins/'.$filename, $result['bytes']);
        $signature->update(['photo' => 'checkins/'.$filename]);

        $checkin = Checkin::create([
            'signature_id' => $signature->id,
            'user_id' => $user->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'selisih_radius_m' => $selisihRadius,
            'address' => $address,
            'client_timestamp' => $validated['client_timestamp'] ?? null,
            'photo_path' => 'checkins/'.$filename,
            'status' => $status,
            'failure_reason' => $authentic ? null : implode('; ', $failures),
            'payload_json' => $validated['payload'],
            'ip_address' => $request->ip(),
            'is_offline' => $isOffline,
            'original_latitude' => $origLat,
            'original_longitude' => $origLng,
            'original_source' => $origSource,
            'original_accuracy' => is_numeric($origAcc) ? (float) $origAcc : null,
            'original_distance_m' => ($origLat !== null) ? round(self::distanceM(
                (float) $validated['latitude'], (float) $validated['longitude'], $origLat, $origLng
            ), 1) : null,
            'original_address' => $origAddr ? mb_substr($origAddr, 0, 500) : null,
            'original_map_url' => ($origLat !== null) ? sprintf('https://www.google.com/maps?q=%.7f,%.7f', $origLat, $origLng) : null,
        ]);

        // Titik bertanggal = tugas sekali jalan → done setelah check-in VALID.
        // Titik tanpa tanggal (berlaku selamanya, mis. pos patroli) = boleh
        // check-in BERULANG, jadi status tetap pending, hanya tautan check-in
        // terakhir yang diperbarui.
        if ($kunjungan && $authentic) {
            $kunjungan->update([
                'kunjungan_status' => $kunjungan->kunjungan_tanggal !== null ? 'done' : 'pending',
                'kunjungan_id_checkin' => $checkin->id,
            ]);
        }

        return response()->json([
            'checkin_id' => $checkin->id,
            'signature_id' => $signature->id,
            'validate_url' => url('/validate/'.$signature->id),
            'photo_url' => url('storage/checkins/'.$filename),
            'status' => $status === 'invalid' ? 'DI LUAR JANGKAUAN' : ($status === 'fake' ? 'PALSU' : 'VALID'),
            'authentic' => $authentic,
            'failures' => $failures,
        ], 201);
    }

    /** Riwayat check-in karyawan. GET /api/absensi/checkins?from=&to= */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);

        $rows = Checkin::with('hasUser')
            ->where('user_id', $request->user()->id)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->latest()->paginate(20);

        return response()->json($rows);
    }

    public static function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        return 2 * $r * asin(sqrt(sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2));
    }

    /**
     * Jarak (meter) dari titik ke polygon GeoJSON.
     * 0 bila di dalam polygon; jarak ke sisi terdekat bila di luar.
     * null bila polygon tidak bisa dibaca.
     */
    public static function distToPolygonM(float $lat, float $lng, string $polygonJson): ?float
    {
        $gj = json_decode($polygonJson, true);
        if (! is_array($gj)) return null;
        $geom = ($gj['type'] ?? null) === 'Feature' ? ($gj['geometry'] ?? null) : $gj;
        $ring = $geom['coordinates'][0] ?? null;
        if (! is_array($ring) || count($ring) < 4) return null;

        // Ray casting: [lng, lat] per GeoJSON
        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float) $ring[$i][0]; $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0]; $yj = (float) $ring[$j][1];
            if ((($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $inside = ! $inside;
            }
        }
        if ($inside) return 0.0;

        $min = null;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $d = self::distToSegmentM($lat, $lng, (float) $ring[$j][1], (float) $ring[$j][0], (float) $ring[$i][1], (float) $ring[$i][0]);
            if ($min === null || $d < $min) $min = $d;
        }

        return $min;
    }

    /** Jarak meter dari titik ke segmen garis (aproksimasi equirectangular, akurat untuk skala kecil). */
    private static function distToSegmentM(float $lat, float $lng, float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $kx = 111320 * cos(deg2rad(($lat1 + $lat2) / 2));
        $ky = 110540;
        $px = ($lng - $lng1) * $kx; $py = ($lat - $lat1) * $ky;
        $dx = ($lng2 - $lng1) * $kx; $dy = ($lat2 - $lat1) * $ky;
        $len2 = $dx * $dx + $dy * $dy;
        $t = $len2 > 0 ? max(0, min(1, ($px * $dx + $py * $dy) / $len2)) : 0;
        $cx = $dx * $t; $cy = $dy * $t;

        return sqrt(($px - $cx) ** 2 + ($py - $cy) ** 2);
    }

    private function reverseGeocode(float $lat, float $lng): ?string
    {
        try {
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => config('app.name').'/1.0 (checkin reverse-geocode)',
                    'Accept-Language' => 'id',
                ])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat, 'lon' => $lng, 'format' => 'json',
                ]);
            if ($res->successful()) {
                return $res->json('display_name');
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
