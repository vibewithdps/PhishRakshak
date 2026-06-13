<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GmailProtectionController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/gmail/callback', [GmailProtectionController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/scan', [ScanController::class, 'scan']);
    Route::get('/scan-history', [ScanController::class, 'history']);

    Route::get('/gmail/status', [GmailProtectionController::class, 'status']);
    Route::get('/gmail/auth-url', [GmailProtectionController::class, 'authUrl']);
    Route::post('/gmail/settings', [GmailProtectionController::class, 'updateSettings']);
    Route::post('/gmail/scan-inbox', [GmailProtectionController::class, 'scanInbox']);
    Route::delete('/gmail/disconnect', [GmailProtectionController::class, 'disconnect']);
});
