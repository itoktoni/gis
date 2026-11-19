<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Toleransi polygon kebun (meter)
    |--------------------------------------------------------------------------
    | Check-in dianggap di dalam kebun bila titiknya masuk polygon ATAU
    | jaraknya ke sisi polygon <= nilai ini (akurasi GPS di lapangan).
    */
    'polygon_tolerance_m' => env('KEBUN_POLYGON_TOLERANCE_M', 100),

    /*
    |--------------------------------------------------------------------------
    | Akurasi GPS maksimal (meter)
    |--------------------------------------------------------------------------
    | Di atas nilai ini check-in tetap TERSIMPAN tapi statusnya tidak VALID
    | (dicatat di failure_reason). Foto + koordinat tetap jadi bukti.
    | Ubah via .env: ABSEN_MAX_ACCURACY_M.
    */
    'max_accuracy_m' => env('ABSEN_MAX_ACCURACY_M', 100),

    /*
    |--------------------------------------------------------------------------
    | Jeda minimal antar check-in VALID (jam) — NONAKTIF (tidak dipakai)
    |--------------------------------------------------------------------------
    | Dulu: check-in VALID berikutnya ditolak (HTTP 429) bila masih dalam jeda.
    | Sekarang dimatikan agar user bisa absen berkali-kali (mis. salah foto
    | bisa langsung ulangi). Config dibiarkan untuk kompatibilitas.
    */
    'min_interval_hours' => env('ABSEN_MIN_INTERVAL_HOURS', 3),

    /*
    /*
    |--------------------------------------------------------------------------
    | Pengingat check-in (notifikasi lokal Android)
    |--------------------------------------------------------------------------
    | Android memuat config ini lewat GET /api/absensi/reminder-config
    | setiap aplikasi dibuka, lalu menjadwalkan notifikasi lokal harian.
    | Ubah lewat .env: REMINDER_ENABLED, REMINDER_START, REMINDER_END,
    | REMINDER_INTERVAL_HOURS, REMINDER_MAX_PER_DAY.
    */
    'reminder' => [
        'enabled' => env('REMINDER_ENABLED', true),
        'start' => env('REMINDER_START', '06:00'),   // jam mulai (WIB)
        'end' => env('REMINDER_END', '17:00'),        // jam selesai (WIB)
        // Interval jam, boleh desimal (contoh: 3, 1, 0.5 = 30 mnt, 0.2 = 12 mnt)
        'interval_hours' => env('REMINDER_INTERVAL_HOURS', 3),
        // Jendela BOLEH lintas tengah malam untuk shift malam
        // (contoh: start 18:00 end 06:00 + interval 3 = bunyi 18, 21, 00, 03, 06).
        // PENTING: max_per_day memotong daftar jam. Contoh shift malam di atas
        // butuh max >= 5; bila kurang, hanya bunyi pertama yang dijadwalkan.
        'max_per_day' => env('REMINDER_MAX_PER_DAY', 8),
        'title' => env('REMINDER_TITLE', 'Waktunya Check-In'),
        'message' => env('REMINDER_MESSAGE', 'Jangan lupa check-in kehadiran hari ini.'),
    ],
];
