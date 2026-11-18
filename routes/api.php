<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);

    Route::auto('/users', UsersController::class, ['name' => 'users']);

    // Absensi lapangan (check-in + titik kunjungan kebun)
    Route::get('/absensi/secret', [\App\Http\Controllers\Api\AbsensiController::class, 'secret']);
    Route::get('/absensi/kunjungan', [\App\Http\Controllers\Api\AbsensiController::class, 'kunjungan']);
    Route::post('/absensi/checkin', [\App\Http\Controllers\Api\AbsensiController::class, 'store']);
    Route::get('/absensi/checkins', [\App\Http\Controllers\Api\AbsensiController::class, 'index']);
    // Tanda tangan digital (port digitalsignature)
    Route::get('/absensi/reminder-config', [\App\Http\Controllers\Api\AbsensiController::class, 'reminderConfig']);
    Route::post('/ttd/sign/image', [\App\Http\Controllers\Api\TtdController::class, 'signImage']);
    Route::post('/ttd/sign/pdf', [\App\Http\Controllers\Api\TtdController::class, 'signPdf']);
    Route::post('/ttd/sign/string', [\App\Http\Controllers\Api\TtdController::class, 'signString']);
    Route::get('/ttd/signatures', [\App\Http\Controllers\Api\TtdController::class, 'signatures']);
    Route::get('/ttd/signatures/stats', [\App\Http\Controllers\Api\TtdController::class, 'stats']);
    Route::get('/ttd/signatures/{id}', [\App\Http\Controllers\Api\TtdController::class, 'signatureShow']);
    Route::delete('/ttd/signatures/{id}', [\App\Http\Controllers\Api\TtdController::class, 'revoke']);
    Route::get('/ttd/keys', [\App\Http\Controllers\Api\TtdController::class, 'keys']);
    Route::post('/ttd/keys/regenerate', [\App\Http\Controllers\Api\TtdController::class, 'regenerateKeys']);
    Route::post('/ttd/validate', [\App\Http\Controllers\Api\TtdController::class, 'validateFile']);
    Route::get('/ttd/validate/{id}', [\App\Http\Controllers\Api\TtdController::class, 'validateById']);
});
