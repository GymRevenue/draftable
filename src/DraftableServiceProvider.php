<?php

declare(strict_types=1);

namespace CnB\Draftable;

use Illuminate\Support\ServiceProvider;

class DraftableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
         $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }
}
