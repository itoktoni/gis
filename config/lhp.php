<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tampilkan input UP & curah hujan di form LHP
    |--------------------------------------------------------------------------
    | false = disembunyikan dulu (mintanya begitu). Nyalakan via .env bila
    | sudah siap dipakai.
    */
    'up_enabled' => env('LHP_UP_ENABLED', false),
    'hujan_enabled' => env('LHP_HUJAN_ENABLED', false),

];
