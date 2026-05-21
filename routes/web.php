<?php

use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldSupervisor\FieldStudentController;
use App\Http\Controllers\InternalSupervisor\SupervisedStudentController;
use App\Http\Controllers\Management\KpAssignmentController;
use App\Http\Controllers\Management\KpAssignmentLogController;
use App\Http\Controllers\Management\KpPeriodController;
use App\Http\Controllers\Management\KpPlaceController;
use App\Http\Controllers\Management\KpPlaceQuotaController;
use App\Http\Controllers\Management\KpDocumentRequirementController;
use App\Http\Controllers\Management\KpRegistrationReviewController;
use App\Http\Controllers\Management\KpQuotaLogController;
use App\Http\Controllers\Management\PlaceSelectionMonitoringController;
use App\Http\Controllers\Management\SelectionLogController;
use App\Http\Controllers\Management\WaitingListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleSelectionController;
use App\Http\Controllers\Student\KpDocumentUploadController;
use App\Http\Controllers\Student\KpRegistrationController;
use App\Http\Controllers\Student\AssignmentController;
use App\Http\Controllers\Student\PlaceSelectionController;
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

        Route::middleware('role:admin,koordinator_kp')->prefix('management')->name('management.')->group(function () {
            Route::resource('kp-periods', KpPeriodController::class);
            Route::resource('kp-places', KpPlaceController::class);
            Route::resource('kp-place-quotas', KpPlaceQuotaController::class);
            Route::post('kp-place-quotas/{quota}/toggle-open', [KpPlaceQuotaController::class, 'toggleOpen'])->name('kp-place-quotas.toggle-open');
            Route::get('kp-quota-logs', [KpQuotaLogController::class, 'index'])->name('kp-quota-logs.index');
            Route::resource('document-requirements', KpDocumentRequirementController::class)->except(['show']);
            Route::get('kp-registrations', [KpRegistrationReviewController::class, 'index'])->name('kp-registrations.index');
            Route::get('kp-registrations/{registration}', [KpRegistrationReviewController::class, 'show'])->name('kp-registrations.show');
            Route::post('kp-registrations/{registration}/documents/{document}/approve', [KpRegistrationReviewController::class, 'approveDocument'])->name('kp-registrations.documents.approve');
            Route::post('kp-registrations/{registration}/documents/{document}/revision', [KpRegistrationReviewController::class, 'revisionDocument'])->name('kp-registrations.documents.revision');
            Route::post('kp-registrations/{registration}/documents/{document}/reject', [KpRegistrationReviewController::class, 'rejectDocument'])->name('kp-registrations.documents.reject');
            Route::post('kp-registrations/{registration}/verify', [KpRegistrationReviewController::class, 'verify'])->name('kp-registrations.verify');
            Route::post('kp-registrations/{registration}/revision', [KpRegistrationReviewController::class, 'revision'])->name('kp-registrations.revision');
            Route::post('kp-registrations/{registration}/reject', [KpRegistrationReviewController::class, 'reject'])->name('kp-registrations.reject');
            Route::get('kp-registrations/{registration}/documents/{document}/download', [KpRegistrationReviewController::class, 'download'])->name('kp-registrations.documents.download');

            Route::get('place-selections', [PlaceSelectionMonitoringController::class, 'index'])->name('place-selections.index');
            Route::get('place-selections/{selection}', [PlaceSelectionMonitoringController::class, 'show'])->name('place-selections.show');
            Route::post('place-selections/{selection}/cancel', [PlaceSelectionMonitoringController::class, 'cancel'])->name('place-selections.cancel');
            Route::get('place-selections/{selection}/move', [PlaceSelectionMonitoringController::class, 'move'])->name('place-selections.move');
            Route::post('place-selections/{selection}/move', [PlaceSelectionMonitoringController::class, 'moveStore'])->name('place-selections.move.store');
            Route::get('waiting-lists', [WaitingListController::class, 'index'])->name('waiting-lists.index');
            Route::post('waiting-lists/{waitingList}/cancel', [WaitingListController::class, 'cancel'])->name('waiting-lists.cancel');
            Route::get('selection-logs', [SelectionLogController::class, 'index'])->name('selection-logs.index');
            Route::resource('kp-assignments', KpAssignmentController::class)->except(['destroy']);
            Route::post('kp-assignments/{assignment}/assign-internal-supervisor', [KpAssignmentController::class, 'assignInternalSupervisor'])->name('kp-assignments.assign-internal-supervisor');
            Route::post('kp-assignments/{assignment}/assign-field-supervisor', [KpAssignmentController::class, 'assignFieldSupervisor'])->name('kp-assignments.assign-field-supervisor');
            Route::post('kp-assignments/{assignment}/cancel', [KpAssignmentController::class, 'cancel'])->name('kp-assignments.cancel');
            Route::post('place-selections/{selection}/create-assignment', [KpAssignmentController::class, 'createFromSelection'])->name('place-selections.create-assignment');
            Route::get('kp-assignment-logs', [KpAssignmentLogController::class, 'index'])->name('kp-assignment-logs.index');
        });

        Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('student.')->group(function () {
            Route::get('pendaftaran-kp', [KpRegistrationController::class, 'index'])->name('kp-registrations.index');
            Route::get('pendaftaran-kp/create', [KpRegistrationController::class, 'create'])->name('kp-registrations.create');
            Route::post('pendaftaran-kp', [KpRegistrationController::class, 'store'])->name('kp-registrations.store');
            Route::get('pendaftaran-kp/{registration}', [KpRegistrationController::class, 'show'])->name('kp-registrations.show');
            Route::post('pendaftaran-kp/{registration}/documents/{requirement}', [KpDocumentUploadController::class, 'store'])->name('kp-registrations.documents.store');
            Route::post('pendaftaran-kp/{registration}/submit', [KpRegistrationController::class, 'submit'])->name('kp-registrations.submit');
            Route::get('pendaftaran-kp/{registration}/documents/{document}/download', [KpRegistrationController::class, 'download'])->name('kp-registrations.documents.download');
            Route::post('pendaftaran-kp/{registration}/cancel', [KpRegistrationController::class, 'cancel'])->name('kp-registrations.cancel');
            Route::get('pemilihan-tempat', [PlaceSelectionController::class, 'index'])->name('place-selections.index');
            Route::get('pemilihan-tempat/{period}', [PlaceSelectionController::class, 'show'])->name('place-selections.show');
            Route::post('pemilihan-tempat/{quota}/pilih', [PlaceSelectionController::class, 'select'])->name('place-selections.select');
            Route::post('pemilihan-tempat/daftar-tunggu', [PlaceSelectionController::class, 'joinWaitingList'])->name('place-selections.waiting-list');
            Route::get('penempatan-kp', [AssignmentController::class, 'show'])->name('assignments.show');
        });

        Route::middleware('role:pembimbing_dalam')->prefix('pembimbing-dalam')->name('internal-supervisor.')->group(function () {
            Route::get('mahasiswa-bimbingan', [SupervisedStudentController::class, 'index'])->name('assignments.index');
            Route::get('mahasiswa-bimbingan/{assignment}', [SupervisedStudentController::class, 'show'])->name('assignments.show');
        });

        Route::middleware('role:pembimbing_lapangan')->prefix('pembimbing-lapangan')->name('field-supervisor.')->group(function () {
            Route::get('mahasiswa-kp', [FieldStudentController::class, 'index'])->name('assignments.index');
            Route::get('mahasiswa-kp/{assignment}', [FieldStudentController::class, 'show'])->name('assignments.show');
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
