<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardKebunController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\ValidateController;
use App\Http\Controllers\WebsiteSettingController;
use App\Models\Notification;
use App\Services\CentrifugoService;
use Buki\AutoRoute\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth')->post('/centrifugo/token', function (Request $request) {
    if (! config('centrifugo.enabled')) {
        return response()->json(['token' => 'disabled']);
    }

    $centrifugo = app(CentrifugoService::class);
    $user = Auth::user();

    if ($request->input('channel')) {
        return response()->json([
            'token' => $centrifugo->generateSubscriptionToken((string) $user->id, $request->input('channel')),
        ]);
    }

    return response()->json([
        'token' => $centrifugo->generateConnectionToken((string) $user->id),
    ]);
});

Route::get('/validate/{id}', [ValidateController::class, 'show'])->name('validate.byId');

Route::middleware(['auth', 'access'])->group(function () {

    Route::get('/validate', [ValidateController::class, 'form'])->name('validate.form');
    Route::post('/validate', [ValidateController::class, 'verify'])->name('validate.check');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('dashboard-kebun', DashboardKebunController::class)->name('dashboard-kebun');

    Route::get('display', DisplayController::class)->name('display');

    Route::auto('/user', 'UsersController', ['name' => 'user']);

    Route::auto('/kebun', 'KebunController', ['name' => 'kebun']);

    Route::auto('/blok', 'BlokController', ['name' => 'blok']);

    Route::auto('/pt', 'PtController', ['name' => 'pt']);

    Route::auto('/distrik', 'DistrikController', ['name' => 'distrik']);

    Route::auto('/vendor', 'VendorController', ['name' => 'vendor']);

    Route::auto('/divisi', 'DivisiController', ['name' => 'divisi']);

    Route::auto('/voucher', 'VoucherController', ['name' => 'voucher']);

    Route::auto('/voucher-status', 'VoucherStatusController', ['name' => 'voucher-status']);

    Route::auto('/masalah', 'MasalahController', ['name' => 'masalah']);

    Route::auto('/cuti', 'CutiController', ['name' => 'cuti']);

    Route::auto('/cuti-report', 'CutiReportController', ['name' => 'cuti-report', 'only' => ['getTable', 'getPrint', 'getExportExcel']]);

    Route::auto('/lhp', 'LhpController', ['name' => 'lhp']);

    Route::auto('/lhp-report', 'LhpReportController', ['name' => 'lhp-report', 'only' => ['getTable', 'getTableBlok', 'getPrint', 'getExportExcel', 'getBlok', 'getExportBlok']]);

    Route::auto('/objek', 'ObjekController', ['name' => 'objek']);

    Route::auto('/kunjungan', 'KunjunganController', ['name' => 'kunjungan']);

    Route::auto('/checkin-report', 'CheckinReportController', ['name' => 'checkin-report', 'only' => ['getTable', 'getShow', 'getSummary', 'getPrint', 'getExportPdf', 'getExportExcel', 'getDelete', 'postDelete']]);

    Route::auto('/kms/document', 'KmsDocumentController', ['name' => 'kms-document']);

    Route::auto('/surat', 'SuratController', ['name' => 'surat']);

    Route::auto('/surat-template', 'SuratTemplateController', ['name' => 'surat-template']);

    Route::get('/native-bridge-test', function () {
        return view('pages.settings.native-bridge-test');
    })->name('native-bridge-test');

    // Kelola APK update otomatis (khusus admin/developer, dicek di controller).
    Route::get('/apk-update', [\App\Http\Controllers\ApkUpdateController::class, 'getForm'])->name('apk-update');
    Route::post('/apk-update', [\App\Http\Controllers\ApkUpdateController::class, 'postSave'])->name('apk-update.save');

    Route::get('/settings/website', [WebsiteSettingController::class, 'index'])->name('settings.website');
    Route::post('/settings/website', [WebsiteSettingController::class, 'save'])->name('settings.website.save');

    Route::prefix('notifications-web')->group(function () {
        Route::get('/', function (Request $request) {
            $notifications = Notification::where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->limit($request->input('limit', 50))
                ->get();

            $unreadCount = Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->count();

            return response()->json([
                'notifications' => $notifications->map(fn ($n) => [
                    'id' => $n->id,
                    'icon' => $n->icon,
                    'iconColor' => $n->icon_color,
                    'title' => $n->title,
                    'body' => $n->body,
                    'url' => $n->url,
                    'type' => $n->type,
                    'read' => $n->read,
                    'time' => $n->created_at?->diffForHumans() ?? '',
                    'created_at' => $n->created_at->toIso8601String(),
                ]),
                'unread_count' => $unreadCount,
            ]);
        });

        Route::put('/{id}/read', function (int $id) {
            $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
            $notification->update(['read' => true]);

            return response()->json(['message' => 'Marked as read']);
        });

        Route::put('/read-all', function () {
            Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json(['message' => 'All marked as read']);
        });
    });
});

require __DIR__.'/settings.php';
