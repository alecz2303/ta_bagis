<?php

namespace Webkul\MPBridge\Providers;

use Illuminate\Support\ServiceProvider;

class MPBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bagisto suele leer payment_methods
        $this->mergeConfigFrom(__DIR__ . '/../Config/payment-methods.php', 'payment_methods');

        // Compat extra (por si tu instalación usa paymentmethods o algo custom)
        $this->mergeConfigFrom(__DIR__ . '/../Config/payment-methods.php', 'paymentmethods');

        $this->mergeConfigFrom(__DIR__ . '/../Config/system.php', 'core');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
