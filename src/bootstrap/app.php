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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'prediction.owner' => \App\Http\Middleware\EnsurePredictionOwner::class,
            'group.member' => \App\Http\Middleware\EnsureGroupMember::class,
            'group.admin' => \App\Http\Middleware\EnsureGroupAdmin::class,
        ]);

        // Trust all proxies (for Render/cloud deployment) - enables HTTPS detection
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
