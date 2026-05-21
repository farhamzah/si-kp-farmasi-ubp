<?php

use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleSelectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/pilih-role', [RoleSelectionController::class, 'index'])->name('role.select');
    Route::post('/set-role/{role:name}', [RoleSelectionController::class, 'store'])->name('role.set');

    Route::middleware('role.selected')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
        Route::get('/profil-saya', [ProfileController::class, 'show'])->name('profile.show');
        Route::redirect('/profile', '/profil-saya')->name('profile.alias');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', UserManagementController::class);
            Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

            Route::get('import-users', [UserImportController::class, 'index'])->name('import-users.index');
            Route::post('import-users/preview', [UserImportController::class, 'preview'])->name('import-users.preview');
            Route::post('import-users/process', [UserImportController::class, 'process'])->name('import-users.process');
            Route::get('import-users/history', [UserImportController::class, 'history'])->name('import-users.history');
            Route::get('import-users/history/{batch}', [UserImportController::class, 'show'])->name('import-users.history.show');
            Route::get('import-users/template/{type}', [UserImportController::class, 'template'])->name('import-users.template');
        });

        Route::get('/mahasiswa/dashboard', fn (DashboardController $controller, Request $request) => $controller->show($request, 'mahasiswa'))
            ->middleware('role:mahasiswa')
            ->name('mahasiswa.dashboard');
        Route::get('/admin/dashboard', fn (DashboardController $controller, Request $request) => $controller->show($request, 'admin'))
            ->middleware('role:admin')
            ->name('admin.dashboard');
        Route::get('/koordinator/dashboard', fn (DashboardController $controller, Request $request) => $controller->show($request, 'koordinator_kp'))
            ->middleware('role:koordinator_kp')
            ->name('koordinator.dashboard');
        Route::get('/pembimbing-dalam/dashboard', fn (DashboardController $controller, Request $request) => $controller->show($request, 'pembimbing_dalam'))
            ->middleware('role:pembimbing_dalam')
            ->name('pembimbing-dalam.dashboard');
        Route::get('/pembimbing-lapangan/dashboard', fn (DashboardController $controller, Request $request) => $controller->show($request, 'pembimbing_lapangan'))
            ->middleware('role:pembimbing_lapangan')
            ->name('pembimbing-lapangan.dashboard');
        Route::get('/penguji/dashboard', fn (DashboardController $controller, Request $request) => $controller->show($request, 'penguji'))
            ->middleware('role:penguji')
            ->name('penguji.dashboard');
    });
});
