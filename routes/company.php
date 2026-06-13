<?php

use App\Http\Controllers\Company\AppointmentController;
use App\Http\Controllers\Company\Auth\LoginController;
use App\Http\Controllers\Company\Auth\RegisterController;
use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DeductionController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\EmployeeLeaveController;
use App\Http\Controllers\Company\ServiceCategoryController;
use App\Http\Controllers\Company\ServiceController;
use App\Http\Controllers\Company\ProfileController;
use App\Http\Controllers\Company\WorkingHoursController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')->name('company.')->group(function () {

    // Guest-only routes
    Route::middleware('company.guest')->group(function () {
        Route::get('/login',    [LoginController::class, 'showLogin'])->name('login');
        Route::post('/login',   [LoginController::class, 'login'])->name('login.attempt');
        Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
        Route::post('/register',[RegisterController::class, 'register'])->name('register.attempt');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware('company.auth')->group(function () {

        // Theme switcher
        Route::get('/theme/{mode}', function (string $mode) {
            return redirect()->back()->cookie('company_theme', $mode === 'light' ? 'light' : 'dark', 60 * 24 * 365);
        })->whereIn('mode', ['light', 'dark'])->name('theme');

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Service categories
        Route::resource('service-categories', ServiceCategoryController::class)
            ->except(['create', 'edit', 'show']);

        // Branches
        Route::resource('branches', BranchController::class)->except(['show']);
        Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
        Route::patch('branches/{branch}/status', [BranchController::class, 'updateStatus'])->name('branches.status');
        Route::post( 'branches/{branch}/regenerate-qr', [BranchController::class, 'regenerateQr'])->name('branches.regenerate-qr');

        // Branch gallery
        Route::get(   'branches/{branch}/gallery',            [BranchController::class, 'gallery'])->name('branches.gallery');
        Route::post(  'branches/{branch}/gallery',            [BranchController::class, 'galleryUpload'])->name('branches.gallery.upload');
        Route::delete('branches/{branch}/gallery/{image}',   [BranchController::class, 'galleryDelete'])->name('branches.gallery.delete');
        Route::post(  'branches/{branch}/gallery/reorder',   [BranchController::class, 'galleryReorder'])->name('branches.gallery.reorder');

        // Working hours (per branch)
        Route::get( 'branches/{branch}/working-hours', [WorkingHoursController::class, 'edit'])->name('branches.working-hours.edit');
        Route::post('branches/{branch}/working-hours', [WorkingHoursController::class, 'update'])->name('branches.working-hours.update');

        // Services (nested under branch, shallow)
        Route::patch('services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])->name('services.toggle-active');
        Route::resource('branches.services', ServiceController::class)->shallow()->except(['show']);

        // Employees (nested under branch, shallow)
        Route::resource('branches.employees', EmployeeController::class)->shallow()->except(['show']);

        // Employee deductions
        Route::get('employees/{employee}/deductions',        [DeductionController::class, 'index'])->name('employees.deductions.index');
        Route::get('employees/{employee}/deductions/create', [DeductionController::class, 'create'])->name('employees.deductions.create');
        Route::post('employees/{employee}/deductions',       [DeductionController::class, 'store'])->name('employees.deductions.store');
        Route::delete('deductions/{deduction}',              [DeductionController::class, 'destroy'])->name('deductions.destroy');

        // Employee leaves
        Route::get('employee-leaves', [EmployeeLeaveController::class, 'index'])->name('employee-leaves.index');
        Route::get('employees/{employee}/leaves/create', [EmployeeLeaveController::class, 'create'])->name('employee-leaves.create');
        Route::post('employees/{employee}/leaves', [EmployeeLeaveController::class, 'store'])->name('employee-leaves.store');
        Route::patch('employee-leaves/{employeeLeave}/status', [EmployeeLeaveController::class, 'updateStatus'])->name('employee-leaves.update-status');
        Route::delete('employee-leaves/{employeeLeave}', [EmployeeLeaveController::class, 'destroy'])->name('employee-leaves.destroy');

        // Profile
        Route::get('/profile',  [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile',  [ProfileController::class, 'update'])->name('profile.update');

        // Appointments
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::get('appointments/branch-data',    [AppointmentController::class, 'branchData'])->name('appointments.branch-data');
        Route::get('appointments/calendar-events',[AppointmentController::class, 'calendarEvents'])->name('appointments.calendar-events');
        Route::get('appointments/staff-events',   [AppointmentController::class, 'staffEvents'])->name('appointments.staff-events');
        Route::resource('appointments', AppointmentController::class)->only(['index', 'show', 'create', 'store']);
    });
});

