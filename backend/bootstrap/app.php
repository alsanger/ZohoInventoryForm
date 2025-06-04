<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // *** ЭТА СТРОКА АКТИВИРУЕТ SANCTUM ДЛЯ SPA-АУТЕНТИФИКАЦИИ ***
        $middleware->statefulApi(); // Это включает EnsureFrontendRequestsAreStateful middleware
        // и автоматически настраивает CORS на основе SANCTUM_STATEFUL_DOMAINS

        // Регистрируем алиас для middleware
        $middleware->alias([
            'zoho.auth' => \App\Http\Middleware\ZohoAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
