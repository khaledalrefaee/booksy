<?php

use App\Http\Controllers\Owner\AppointmentController;
use App\Http\Controllers\Owner\Auth\LoginController;
use App\Http\Controllers\Owner\BranchController;
use App\Http\Controllers\Owner\CompanyController;
use App\Http\Controllers\Owner\CategoryController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\EmployeeLeaveController;
use App\Http\Controllers\Owner\ProfileController;
use App\Http\Controllers\Owner\ServiceCategoryController;
use App\Http\Controllers\Owner\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('owner')->name('owner.')->group(function () {

    // Auth routes (guest only)
    Route::middleware('owner.guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware('owner.auth')->group(function () {
        Route::get('/theme/{mode}', function (string $mode) {
            $theme = $mode === 'light' ? 'light' : 'dark';

            return redirect()
                ->back()
                ->cookie('owner_theme', $theme, 60 * 24 * 365);
        })->whereIn('mode', ['light', 'dark'])->name('theme');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::resource('categories', CategoryController::class);
        Route::resource('service-categories', ServiceCategoryController::class)->except(['create', 'edit', 'show']);

        Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])
            ->name('companies.update-status');
        Route::resource('companies', CompanyController::class)->except(['create', 'edit']);

        Route::get('branches/{branch}/working-hours', [BranchController::class, 'createWorkingHours'])
            ->name('branches.working-hours.create');
        Route::post('branches/{branch}/working-hours', [BranchController::class, 'storeWorkingHours'])
            ->name('branches.working-hours.store');
        Route::post('branches/{branch}/working-hours/skip', [BranchController::class, 'skipWorkingHours'])
            ->name('branches.working-hours.skip');

        Route::post('branches/{branch}/employees/skip', [EmployeeController::class, 'skipEmployees'])
            ->name('branches.employees.skip');

        Route::resource('branches', BranchController::class)->except(['show']);

        Route::patch('services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])
            ->name('services.toggle-active');

        Route::resource('branches.services', ServiceController::class)
            ->shallow()
            ->except(['show']);

        Route::resource('branches.employees', EmployeeController::class)
            ->shallow()
            ->except(['show']);

        // Employee leaves
        Route::get('employee-leaves', [EmployeeLeaveController::class, 'index'])->name('employee-leaves.index');
        Route::get('employees/{employee}/leaves/create', [EmployeeLeaveController::class, 'create'])->name('employee-leaves.create');
        Route::post('employees/{employee}/leaves', [EmployeeLeaveController::class, 'store'])->name('employee-leaves.store');
        Route::patch('employee-leaves/{employeeLeave}/status', [EmployeeLeaveController::class, 'updateStatus'])->name('employee-leaves.update-status');
        Route::delete('employee-leaves/{employeeLeave}', [EmployeeLeaveController::class, 'destroy'])->name('employee-leaves.destroy');

        Route::resource('appointments', AppointmentController::class)->only(['index', 'show']);
    });
});
