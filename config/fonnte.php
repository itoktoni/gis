<?php

return [
    'token' => env('FONNTE_TOKEN', ''),
    'group_id' => env('FONNTE_LHP_GROUP_ID', ''),
    'country_code' => env('FONNTE_COUNTRY_CODE', '62'),
    // Jam summary + pengecekan LHP harian (format HH:MM, cron via schedule:run)
    'lhp_reminder_at' => env('FONNTE_LHP_REMINDER_AT', '12:00'),
];
