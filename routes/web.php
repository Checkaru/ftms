<?php

use App\Enums\UserRole;
use App\Http\Controllers\Coordinator\OrganizationController;
use App\Http\Controllers\Coordinator\PlacementController;
use App\Http\Controllers\Coordinator\TrainingPeriodController;
use App\Http\Controllers\Coordinator\UserController;
use App\Http\Controllers\Coordinator\ReportController;
use App\Http\Controllers\Coordinator\DashboardController;
use App\Http\Controllers\Student\AttendanceLogController as StudentAttendanceLogController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Field\ReviewController as FieldReviewController;
use App\Http\Controllers\Academic\DashboardController as AcademicDashboardController;
use App\Http\Controllers\Academic\PlacementController as AcademicPlacementController;
use App\Http\Controllers\Academic\EvaluationController as AcademicEvaluationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Landing: guests go to login, authenticated users to their role home.
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Role-aware dispatcher. Breeze redirects here after login; it forwards each
// user to the section that is their home.
Route::get('/dashboard', function () {
    $home = match (Auth::user()->role) {
        UserRole::Coordinator => 'coordinator.dashboard',
        UserRole::FieldSupervisor => 'field.dashboard',
        UserRole::AcademicSupervisor => 'academic.dashboard',
        UserRole::Student => 'student.dashboard',
    };

    return redirect()->route($home);
})->middleware('auth')->name('dashboard');

// Shared: profile management for every authenticated role.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Coordinator section — manages organisations, periods, placements, users.
Route::middleware(['auth', 'role:coordinator'])
    ->prefix('coordinator')
    ->name('coordinator.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('organizations', OrganizationController::class)->except('show');
        Route::resource('periods', TrainingPeriodController::class)->except('show');
        Route::resource('placements', PlacementController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/placements/{placement}', [ReportController::class, 'show'])->name('reports.show');
    });

// Student section — logs attendance, tracks progress.
Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/', StudentDashboardController::class)->name('dashboard');
        Route::resource('logs', StudentAttendanceLogController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });

// Field supervisor section — the approval queue for their organisation.
Route::middleware(['auth', 'role:field_supervisor'])
    ->prefix('field')
    ->name('field.')
    ->group(function () {
        Route::get('/', [FieldReviewController::class, 'index'])->name('dashboard');
        Route::post('logs/bulk-approve', [FieldReviewController::class, 'bulkApprove'])->name('logs.bulkApprove');
        Route::post('logs/{log}/approve', [FieldReviewController::class, 'approve'])->name('logs.approve');
        Route::post('logs/{log}/reject', [FieldReviewController::class, 'reject'])->name('logs.reject');
        Route::post('logs/{log}/revert', [FieldReviewController::class, 'revert'])->name('logs.revert');
    });

// Academic supervisor section — assigned students, evaluations, grade.
Route::middleware(['auth', 'role:academic_supervisor'])
    ->prefix('academic')
    ->name('academic.')
    ->group(function () {
        Route::get('/', AcademicDashboardController::class)->name('dashboard');
        Route::get('placements/{placement}', [AcademicPlacementController::class, 'show'])->name('placements.show');
        Route::get('placements/{placement}/evaluation', [AcademicEvaluationController::class, 'edit'])->name('evaluation.edit');
        Route::put('placements/{placement}/evaluation', [AcademicEvaluationController::class, 'update'])->name('evaluation.update');
    });

require __DIR__.'/auth.php';
