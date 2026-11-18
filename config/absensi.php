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
        'max_per_day' => env('REMINDER_MAX_PER_DAY', 3),
        'title' => env('REMINDER_TITLE', 'Waktunya Check-In'),
        'message' => env('REMINDER_MESSAGE', 'Jangan lupa check-in kehadiran hari ini.'),
    ],
];
