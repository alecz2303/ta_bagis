<?php

use Illuminate\Support\Facades\Route;
use Davidsonts\MercadoPago\Http\Controllers\MercadoPagoController;

Route::post('/mercadopago/webhook', [MercadoPagoController::class, 'webhook'])
    ->name('mercadopago.webhook');
