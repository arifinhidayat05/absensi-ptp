<?php

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
        $middleware->alias([
            'operator' => \App\Http\Middleware\OperatorMiddleware::class,
            'karyawan' => \App\Http\Middleware\KaryawanMiddleware::class,
            'security.shield' => \App\Http\Middleware\SecurityShieldMiddleware::class,
        ]);

        // Pasang firewall WAF dan security shield pada seluruh request web
        $middleware->web(append: [
            \App\Http\Middleware\SecurityShieldMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
