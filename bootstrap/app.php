<?php

use App\Http\Middleware\EnsureFirstLoginStepCompleted;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'first-login.step' => EnsureFirstLoginStepCompleted::class,
        ]);

        // L'app tourne derrière un reverse proxy (cf. docker-compose.yml) ;
        // sans ça, $request->ip() retourne l'IP du proxy et pas celle du
        // client, ce qui fausse le rate-limiting et les logs ldap-security.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
