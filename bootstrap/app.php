<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AgentApiMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\EmployeeMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.custom' => AuthMiddleware::class,
            'admin' => AdminMiddleware::class,
            'employee' => EmployeeMiddleware::class,
            'agent.api' => AgentApiMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The bell's poll and the push endpoints are only ever consumed by
        // JavaScript, so a 404 or a validation failure has to come back as JSON
        // instead of an HTML error page. Keyed by route name because the
        // /notifications/all page shares the /notifications path prefix.
        $jsonRoutes = [
            'notifications.index',
            'notifications.read',
            'notifications.read-all',
            'notifications.destroy',
            'push.key',
            'push.subscribe',
            'push.unsubscribe',
        ];

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || in_array($request->route()?->getName(), $jsonRoutes, true),
        );
    })->create();
