<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.level'            => \App\Http\Middleware\CheckLevel::class,
            'check.single.session'   => \App\Http\Middleware\CheckSingleSession::class,
            'check.maintenance'      => \App\Http\Middleware\CheckMaintenance::class,
            'check.ip.whitelist'     => \App\Http\Middleware\CheckIpWhitelist::class,
            'check.session.timeout'  => \App\Http\Middleware\CheckSessionTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
