<?php


use App\Http\Controllers\MxPostalController;

Route::get('/mx/cp/{cp}', [MxPostalController::class, 'lookup'])
    ->where('cp', '\d{5}');


Route::any('/__ping', function () {
    \Log::info('PING OK', ['ip' => request()->ip(), 'ua' => request()->userAgent()]);
    return response()->json(['ok' => true]);
});