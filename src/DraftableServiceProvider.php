<?php

declare(strict_types=1);

namespace CapeAndBay\Draftable;

use Illuminate\Support\ServiceProvider;

class DraftableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }
}
