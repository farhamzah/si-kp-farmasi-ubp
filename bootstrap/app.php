<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\EnsureRoleSelected;
use App\Console\Commands\CoreHealthCheckCommand;
use App\Console\Commands\CoreModePreflightCommand;
use App\Console\Commands\SyncCoreMappingCommand;
use App\Console\Commands\AuthBridgeCheckCommand;
use App\Console\Commands\AuthBridgeSmokeTestCommand;
use App\Console\Commands\AuthModeCommand;
use App\Console\Commands\DisplayAdapterCheckCommand;
use App\Console\Commands\MasterDataReadCheckCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

require_once __DIR__.'/../app/helpers.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        AuthBridgeCheckCommand::class,
        AuthBridgeSmokeTestCommand::class,
        AuthModeCommand::class,
        CoreHealthCheckCommand::class,
        CoreModePreflightCommand::class,
        DisplayAdapterCheckCommand::class,
        MasterDataReadCheckCommand::class,
        SyncCoreMappingCommand::class,
    ])
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
