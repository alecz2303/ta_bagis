<?php

namespace Webkul\TodoArtesanal\Providers;

use Illuminate\Support\ServiceProvider;

class TodoArtesanalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('themes/todoartesanal/views'),
        ], 'todoartesanal-views');
    }
}