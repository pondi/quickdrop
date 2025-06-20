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
        // Add Inertia middleware
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\AuditMiddleware::class,
        ]);

        // Add our custom route middleware
        $middleware->alias([
            'verify.token'         => \App\Http\Middleware\VerifyUploadToken::class,
            'validate.upload.size' => \App\Http\Middleware\ValidateUploadSize::class,
            'backstage.auth'       => \App\Http\Middleware\BackstageAuth::class,
            'quickdrop.auth'       => \App\Http\Middleware\QuickDropAuth::class,
            'audit'                => \App\Http\Middleware\AuditMiddleware::class,
        ]);
        
        // Configure redirect for guests
        $middleware->redirectGuestsTo(function ($request) {
            // If accessing backstage routes, redirect to backstage login
            if ($request->is('backstage/*') || $request->is('backstage')) {
                return route('backstage.login');
            }
            
            // For all other routes, redirect to QuickDrop login
            return route('quickdrop.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
