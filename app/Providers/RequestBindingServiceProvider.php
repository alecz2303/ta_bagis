<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class RequestBindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ApiPlatform intenta usar request durante register, así que lo garantizamos.
        if (!$this->app->bound('request')) {
            $this->app->singleton('request', function () {
                return Request::capture();
            });

            $this->app->alias('request', Request::class);
        }
    }
}