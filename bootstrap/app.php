<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        using: function () {
            Route::group([], function ($router) {
                require __DIR__ . '/../routes/web.php';
                require __DIR__ . '/../routes/webPaycoLink.php';
                require __DIR__ . '/../routes/webLatam.php';
                require __DIR__ . '/../routes/webGeneral.php';
                require __DIR__ . '/../routes/webButtons.php';
                require __DIR__ . '/../routes/webShopingcartDelivery.php';
                require __DIR__ . '/../routes/webVende.php';
            });

        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'authorize' => App\Http\Middleware\Authorize::class,
            'jwt.auth' => App\Http\Middleware\JwtMiddleware::class,
            'vtex.pse' => App\Http\Middleware\VtexPseMiddleware::class,
            'vtex.return.pse' => App\Http\Middleware\VtexReturnPseMiddleware::class,
            'activeSubscription' => App\Http\Middleware\ActiveSubscription::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })
    ->create();
