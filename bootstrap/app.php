<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <<< TAMBAHKAN INI
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    
    /*
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })*/
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\AuditActionMiddleware::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'audit.action' => \App\Http\Middleware\AuditActionMiddleware::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\AuditActionMiddleware::class,
        ]);
    })
    
    
    
    
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
