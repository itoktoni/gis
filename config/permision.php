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
    'approve', 'sisa', 'report', 'downloaddocx',
];

// Semua prefix modul yang ada di routes/web.php.
$allModules = [
    'dashboard',
    'user',
    'kebun',
    'pt',
    'distrik',
    'blok',
    'vendor',
    'divisi',
    'voucher',
    'voucher-status',
    'masalah',
    'cuti',
    'cuti-report',
    'lhp',
    'lhp-report',
    'objek',
    'kunjungan',
    'checkin-report',
    'kms-document',
    'surat',
    'surat-template',
    'settings',
    'native-bridge-test',
    'apk-update',
    'notifications-web',
];

// Modul yang BOLEH diakses per role fungsi.
// Role "user" (Pengguna Biasa): hanya Dashboard + KMS.
$allow = [
    'user' => ['dashboard', 'kms-document'],
    'tanaman' => ['dashboard', 'lhp', 'lhp-report', 'kunjungan', 'checkin-report', 'blok'],
    'finance' => ['dashboard', 'voucher', 'voucher-status', 'vendor'],
    'sekretaris' => ['dashboard', 'surat', 'surat-template'],
    'distrik' => ['dashboard', 'kebun', 'blok', 'lhp', 'lhp-report', 'masalah', 'kunjungan', 'checkin-report'],
];

$restrict = [];

// Pengguna Biasa: KMS hanya baca + tanya jawab, tanpa tulis/hapus.
$restrict['user']['kms-document'] = ['create', 'save', 'update', 'delete'];

foreach ($allow as $role => $modules) {
    foreach (array_diff($allModules, $modules) as $blocked) {
        $restrict[$role][$blocked] = $denyActions;
    }
}

return $restrict;
