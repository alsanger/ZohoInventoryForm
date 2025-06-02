<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Laravel's default global middleware
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class, // Optional, uncomment if you're using Laravel's built-in session authentication
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class, // Essential for web routes and forms
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // *** THIS IS CRITICAL FOR SANCTUM SPA AUTHENTICATION ***
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes.
     *
     * @var array<string, class-string|string>
     */
    protected $aliases = [
        // 'auth' => \App\Http\Middleware\Authenticate::class,
        // 'cache.headers' => \Illuminate\Http\Middleware\HandleCacheHeaders::class,
        // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
        // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // 'password.confirm' => \App\Http\Middleware\RequirePasswordConfirmation::class,
        // 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        // 'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
