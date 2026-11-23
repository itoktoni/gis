<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Define menu items for desktop sidebar, mobile drawer, and bottom nav.
    | Each item: route (string), icon (string), label (string)
    | Sections: label (string), items (array)
    | Bottom nav: only 5 items max, uses short label
    |
    */

    'sidebar' => [
        [
            'label' => null,
            'items' => [
                ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
            ],
        ],
        [
            'label' => 'Produksi (LHP)',
            'items' => [
                ['route' => 'dashboard-kebun', 'icon' => 'dashboard', 'label' => 'Dashboard Kebun'],
                ['route' => 'display', 'icon' => 'tv', 'label' => 'Display TV'],
                ['route' => 'lhp.getTable', 'icon' => 'agriculture', 'label' => 'LHP Harian', 'match' => ['lhp.getTable', 'lhp.getCreate', 'lhp.getUpdate', 'lhp.getDelete', 'lhp.getShow']],
                ['route' => 'lhp-report.getTable', 'icon' => 'summarize', 'label' => 'Report LHP', 'match' => ['lhp-report.getTable', 'lhp-report.getPrint', 'lhp-report.getExportExcel']],
                ['route' => 'lhp-report.getTableBlok', 'icon' => 'grid_on', 'label' => 'Report per Blok', 'match' => ['lhp-report.getTableBlok', 'lhp-report.getBlok', 'lhp-report.getExportBlok']],
            ],
        ],
        [
            'label' => 'Pembayaran Vendor',
            'items' => [
                ['route' => 'voucher.getTable', 'icon' => 'receipt_long', 'label' => 'Dokumen Voucher', 'match' => ['voucher.*']],
                ['route' => 'vendor.getTable', 'icon' => 'store', 'label' => 'Vendor', 'match' => ['vendor.*']],
                ['route' => 'voucher-status.getTable', 'icon' => 'format_list_numbered', 'label' => 'Status Dokumen', 'match' => ['voucher-status.*']],
            ],
        ],
        [
            'label' => 'Monitoring',
            'items' => [
                ['route' => 'masalah.getTable', 'icon' => 'report_problem', 'label' => 'Permasalahan', 'match' => ['masalah.*']],
                ['route' => 'cuti.getTable', 'icon' => 'event_available', 'label' => 'Pengajuan Cuti', 'match' => ['cuti.*']],
                ['route' => 'cuti-report.getTable', 'icon' => 'summarize', 'label' => 'Report Cuti', 'match' => ['cuti-report.*']],
            ],
        ],
        [
            'label' => 'Absensi',
            'items' => [
                ['route' => 'kunjungan.getTable', 'icon' => 'push_pin', 'label' => 'Titik Kunjungan', 'match' => ['kunjungan.*']],
                ['route' => 'checkin-report.getTable', 'icon' => 'fact_check', 'label' => 'Report Check-In', 'match' => ['checkin-report.*']],
                ['route' => 'validate.form', 'icon' => 'verified', 'label' => 'Validasi Foto', 'match' => ['validate.*']],
            ],
        ],
        [
            'label' => 'GIS Sawit',
            'items' => [
                ['route' => 'kebun.getTable', 'icon' => 'map', 'label' => 'Kebun Sawit', 'match' => ['kebun.*']],
                ['route' => 'blok.getTable', 'icon' => 'grid_on', 'label' => 'Blok Sawit', 'match' => ['blok.*']],
                ['route' => 'objek.getTable', 'icon' => 'push_pin', 'label' => 'Objek / POS', 'match' => ['objek.*']],
            ],
        ],
        [
            'label' => 'KMS',
            'items' => [
                ['route' => 'kms-document.getTable', 'icon' => 'folder', 'label' => 'Dokumen', 'match' => ['kms-document.getTable', 'kms-document.getCreate', 'kms-document.getUpdate', 'kms-document.getDelete', 'kms-document.getDownload']],
                ['route' => 'kms-document.getChat', 'icon' => 'forum', 'label' => 'Tanya KMS'],
                ['route' => 'divisi.getTable', 'icon' => 'groups', 'label' => 'Divisi', 'match' => ['divisi.*']],
            ],
        ],
        [
            'label' => 'Persuratan',
            'items' => [
                ['route' => 'surat.getTable', 'icon' => 'mail', 'label' => 'Surat Masuk/Keluar', 'match' => ['surat.*']],
                ['route' => 'surat-template.getTable', 'icon' => 'description', 'label' => 'Template Surat', 'match' => ['surat-template.*']],
            ],
        ],
        [
            'label' => 'Master Data',
            'items' => [
                ['route' => 'pt.getTable', 'icon' => 'business', 'label' => 'PT / Grup', 'match' => ['pt.*']],
                ['route' => 'distrik.getTable', 'icon' => 'map', 'label' => 'Distrik', 'match' => ['distrik.*']],
                ['route' => 'user.getTable', 'icon' => 'manage_accounts', 'label' => 'Users', 'match' => ['user.*']],
            ],
        ],
        // [
        //     'label' => 'CMS',
        //     'items' => [
        //         ['route' => 'cms-type.getTable', 'icon' => 'category', 'label' => 'Types', 'match' => ['cms-type.*']],
        //         ['route' => 'field.getTable', 'icon' => 'input', 'label' => 'Fields', 'match' => ['field.*']],
        //         ['route' => 'section.getTable', 'icon' => 'view_agenda', 'label' => 'Sections', 'match' => ['section.*']],
        //         ['route' => 'content.getTable', 'icon' => 'article', 'label' => 'Content', 'match' => ['content.*']],
        //         ['route' => 'category.getTable', 'icon' => 'sell', 'label' => 'Categories', 'match' => ['category.*']],
        //         ['route' => 'tag.getTable', 'icon' => 'label', 'label' => 'Tags', 'match' => ['tag.*']],
        //         ['route' => 'menu.getTable', 'icon' => 'menu', 'label' => 'Menus', 'match' => ['menu.*']],
        //     ],
        // ],
        [
            'label' => 'Settings',
            'items' => [
                ['route' => 'settings.website', 'icon' => 'language', 'label' => 'Website'],
                ['route' => 'settings.env', 'icon' => 'settings', 'label' => 'Environment'],
                ['route' => 'apk-update', 'icon' => 'system_update', 'label' => 'Update APK', 'match' => ['apk-update*']],
            ],
        ],
    ],

    // Bottom nav per role (mobile). Key = role, fallback ke 'default'.
    // Item tengah (index tengah) dirender menonjol.
    'bottom_nav' => [
        'default' => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'lhp.getTable', 'icon' => 'agriculture', 'label' => 'LHP'],
            ['route' => 'voucher.getTable', 'icon' => 'receipt_long', 'label' => 'Voucher'],
            ['route' => 'surat.getTable', 'icon' => 'mail', 'label' => 'Surat'],
            ['route' => 'kebun.getTable', 'icon' => 'map', 'label' => 'Kebun'],
        ],
        'user' => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'kms-document.getTable', 'icon' => 'folder', 'label' => 'Dokumen'],
            ['route' => 'kms-document.getChat', 'icon' => 'forum', 'label' => 'Tanya KMS'],
        ],
        'tanaman' => [
            ['route' => 'dashboard-kebun', 'icon' => 'dashboard', 'label' => 'Dash'],
            ['route' => 'lhp.getTable', 'icon' => 'agriculture', 'label' => 'LHP'],
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'lhp-report.getTable', 'icon' => 'summarize', 'label' => 'Report'],
        ],
        'finance' => [
            ['route' => 'voucher.getTable', 'icon' => 'receipt_long', 'label' => 'Voucher'],
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'vendor.getTable', 'icon' => 'store', 'label' => 'Vendor'],
        ],
        'sekretaris' => [
            ['route' => 'surat.getTable', 'icon' => 'mail', 'label' => 'Surat'],
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'surat-template.getTable', 'icon' => 'description', 'label' => 'Template'],
        ],
        'distrik' => [
            ['route' => 'dashboard-kebun', 'icon' => 'dashboard', 'label' => 'Dash'],
            ['route' => 'kebun.getTable', 'icon' => 'map', 'label' => 'Kebun'],
            ['route' => 'lhp.getTable', 'icon' => 'agriculture', 'label' => 'LHP'],
            ['route' => 'masalah.getTable', 'icon' => 'report_problem', 'label' => 'Masalah'],
            ['route' => 'lhp-report.getTable', 'icon' => 'summarize', 'label' => 'Report'],
        ],
    ],

];
