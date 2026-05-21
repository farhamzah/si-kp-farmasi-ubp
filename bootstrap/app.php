<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\EnsureRoleSelected;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => CheckUserActive::class,
            'role.selected' => EnsureRoleSelected::class,
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi Anda telah kedaluwarsa. Silakan login kembali.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('status', 'Sesi login kedaluwarsa. Silakan login kembali.');
        });
    })->create();
