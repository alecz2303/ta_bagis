<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Webkul\MPBridge\Http\Controllers\MPBridgeController;

Route::group(['middleware' => ['web']], function () {
    Route::get('mpbridge/redirect', [MPBridgeController::class, 'redirect'])->name('mpbridge.redirect');
    Route::get('mpbridge/return', [MPBridgeController::class, 'return'])->name('mpbridge.return');
});

Route::post('mpbridge/webhook', [MPBridgeController::class, 'webhook'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('mpbridge.webhook');
