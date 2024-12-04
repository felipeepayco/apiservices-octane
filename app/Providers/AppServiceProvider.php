<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //AVOID USING SINGLETONS WITH LARAVEL OCTANE, USE BIND INSTEAD
        $this->app->bind('api.version', function () {
            $version = $_SERVER['HTTP_VERSION'] ?? null;
            if ($version == "1") {
                return null;
            }
            return $version ? 'App\Http\Controllers\V' . $version : 'App\Http\Controllers';
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
