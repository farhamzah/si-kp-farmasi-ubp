<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\EnsureRoleSelected;
use App\Console\Commands\AcademicUnitCleanupCommand;
use App\Console\Commands\CoreHealthCheckCommand;
use App\Console\Commands\CoreAcademicUnitCheckCommand;
use App\Console\Commands\CoreMappingCoverageCommand;
use App\Console\Commands\CoreModePreflightCommand;
use App\Console\Commands\ExternalDocumentReferencePreviewCommand;
use App\Console\Commands\SyncCoreMappingCommand;
use App\Console\Commands\AuthBridgeCheckCommand;
use App\Console\Commands\AuthBridgeSmokeTestCommand;
use App\Console\Commands\AuthModeCommand;
use App\Console\Commands\DisplayAdapterCheckCommand;
use App\Console\Commands\IntegrationGapCheckCommand;
use App\Console\Commands\MasterDataReadCheckCommand;
use App\Console\Commands\ProductionReadinessGateCommand;
use App\Console\Commands\ProvisionCoreBridgeUserCommand;
use App\Console\Commands\ReleaseCandidateGateCommand;
use App\Console\Commands\ReleaseSensitiveScanCommand;
use App\Console\Commands\SafaPublicInfoPreviewCommand;
use App\Console\Commands\StagingRehearsalCheckCommand;
use App\Console\Commands\TuDocumentPayloadPreviewCommand;
use App\Console\Commands\UiReadinessCheckCommand;
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
        AcademicUnitCleanupCommand::class,
        AuthBridgeCheckCommand::class,
        AuthBridgeSmokeTestCommand::class,
        AuthModeCommand::class,
        CoreAcademicUnitCheckCommand::class,
        CoreHealthCheckCommand::class,
        CoreMappingCoverageCommand::class,
        CoreModePreflightCommand::class,
        DisplayAdapterCheckCommand::class,
        ExternalDocumentReferencePreviewCommand::class,
        IntegrationGapCheckCommand::class,
        MasterDataReadCheckCommand::class,
        ProductionReadinessGateCommand::class,
        ProvisionCoreBridgeUserCommand::class,
        ReleaseCandidateGateCommand::class,
        ReleaseSensitiveScanCommand::class,
        SafaPublicInfoPreviewCommand::class,
        StagingRehearsalCheckCommand::class,
        SyncCoreMappingCommand::class,
        TuDocumentPayloadPreviewCommand::class,
        UiReadinessCheckCommand::class,
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
