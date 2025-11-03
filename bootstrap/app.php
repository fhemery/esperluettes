<?php

use App\Domains\Auth\Public\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return response()->view('shared::errors.404', [], 404);
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                return response()->view('shared::errors.419', [], 419);
            }
        });

        // We should not catch 500 here, the error page is configured in /resources/views/errors/500.blade.php
    })->create();
