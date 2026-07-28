<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);

        $middleware->redirectGuestsTo(function () {
            return route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (QueryException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $message = $e->getMessage();
            $isConnectionError = str_contains($message, 'SQLSTATE[HY000] [2002]')
                || str_contains($message, 'SQLSTATE[HY000] [2006]')
                || str_contains($message, 'SQLSTATE[HY000] [1040]')
                || str_contains($message, 'Connection refused')
                || str_contains($message, 'Operation not permitted')
                || str_contains($message, 'getaddrinfo')
                || str_contains($message, 'server has gone away');

            report($e);

            return response()->json([
                'success' => false,
                'message' => $isConnectionError
                    ? 'Unable to reach the database. Please try again in a moment.'
                    : 'A database error occurred. Please try again.',
            ], 503);
        });
    })->create();
