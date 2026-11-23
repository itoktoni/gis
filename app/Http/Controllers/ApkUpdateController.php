<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApkUpdateController extends Controller
{
    private const JSON_PATH = 'apk-version.json';

    private const APK_PATH = 'public/apk/apn-monitoring.apk';

    /** Baca info versi saat ini (untuk form web + API publik). */
    public static function current(): array
    {
        $default = [
            'version_code' => 3,
            'version_name' => '1.2.0',
            'changelog' => '',
            'force' => false,
        ];

        if (Storage::exists(self::JSON_PATH)) {
            $saved = json_decode(Storage::get(self::JSON_PATH), true) ?? [];
            $default = array_merge($default, array_intersect_key($saved, $default));
        }

        return $default;
    }

    private function ensureAdmin(): void
    {
        abort_unless(in_array(auth()->user()->role ?? '', ['admin', 'developer']), 403);
    }

    /** GET /apk-update — form upload versi baru. */
    public function getForm()
    {
        $this->ensureAdmin();

        return view('pages.apkupdate.form', ['info' => self::current()]);
    }

    /** POST /apk-update — simpan APK + info versi. */
    public function postSave(Request $request)
    {
        $this->ensureAdmin();

        $current = self::current();

        $data = $request->validate([
            'version_code' => 'required|integer|min:'.($current['version_code'] + 1),
            'version_name' => 'required|string|max:50',
            'changelog' => 'nullable|string|max:2000',
            'force' => 'nullable|boolean',
            'apk' => 'nullable|file|extensions:apk|max:102400',
        ], [
            'version_code.min' => 'Version code harus lebih dari '.$current['version_code'].' (versi saat ini).',
        ]);

        if ($request->hasFile('apk')) {
            // Simpan LANGSUNG ke public/apk (folder yang di-download HP),
            // bukan ke storage (itu tidak ter-publish ke URL /apk/...).
            @mkdir(public_path('apk'), 0755, true);
            $request->file('apk')->move(public_path('apk'), 'apn-monitoring.apk');
        }

        Storage::put(self::JSON_PATH, json_encode([
            'version_code' => (int) $data['version_code'],
            'version_name' => $data['version_name'],
            'changelog' => $data['changelog'] ?? '',
            'force' => (bool) ($data['force'] ?? false),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->route('apk-update')->with('success', TOAST_SUCCESS);
    }

    /** GET /api/app/version (publik, tanpa login) — dibaca aplikasi Android. */
    public function version()
    {
        return response()->json(array_merge(self::current(), [
            'apk_url' => url('apk/apn-monitoring.apk'),
        ]));
    }
}
