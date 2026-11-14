<?php

/*
|--------------------------------------------------------------------------
| Permission restriction matrix (deny-based).
|--------------------------------------------------------------------------
| $restrict[role][module][] = aksi yang DITOLAK (BasePolicy me-deny).
| Kunci modul cukup prefix (lhp, voucher, ...) — BasePolicy juga
| mencocokkan nama route penuh (voucher.getTable).
|
| $allow di bawah adalah sumber tunggal: semua modul di luar daftar
| allow otomatis di-deny untuk semua aksi yang dikenal.
*/

// Semua aksi policy yang dikenal (BasePolicy + custom per modul).
$denyActions = [
    'table', 'show', 'create', 'save', 'update', 'delete',
    'prepare', 'prepareSo', 'storeship',
    'status', 'pay', 'import', 'exportexcel', 'print',
    'exportpdf', 'exportdocx', 'generate', 'chat', 'download',
];

// Semua prefix modul yang ada di routes/web.php.
$allModules = [
    'dashboard',
    'user',
    'kebun',
    'pt',
    'distrik',
    'vendor',
    'divisi',
    'voucher',
    'voucher-status',
    'masalah',
    'lhp',
    'lhp-report',
    'objek',
    'kms-document',
    'surat',
    'surat-template',
    'settings',
    'native-bridge-test',
    'notifications-web',
];

// Modul yang BOLEH diakses per role fungsi.
$allow = [
    'tanaman' => ['dashboard', 'lhp', 'lhp-report'],
    'finance' => ['dashboard', 'voucher', 'voucher-status', 'vendor'],
    'sekretaris' => ['dashboard', 'surat', 'surat-template'],
    'distrik' => ['dashboard', 'kebun', 'lhp', 'lhp-report', 'masalah'],
];

$restrict = [];

foreach ($allow as $role => $modules) {
    foreach (array_diff($allModules, $modules) as $blocked) {
        $restrict[$role][$blocked] = $denyActions;
    }
}

return $restrict;
