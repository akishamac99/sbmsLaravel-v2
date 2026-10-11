<?php

use App\Http\Middleware\EnsureManagerPermission;
use App\Http\Middleware\EnsureRole;
use App\Support\Home;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short names we can use in routes: role:manager and perm:bookings,edit
        $middleware->alias([
            'role' => EnsureRole::class,
            'perm' => EnsureManagerPermission::class,
        ]);

        // Render sits behind a proxy that handles https, so trust it for the real scheme and client.
        $middleware->trustProxies(at: '*');

        // PayMongo and Meta cannot send a CSRF token, so their webhooks are checked by signature instead.
        $middleware->validateCsrfTokens(except: ['webhooks/paymongo', 'webhooks/messenger']);

        // Not logged in -> send to the login page.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Already logged in -> send to your own dashboard.
        $middleware->redirectUsersTo(fn () => Home::url());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
