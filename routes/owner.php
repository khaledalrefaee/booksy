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
use App\Http\Controllers\Owner\LocationController;
use App\Http\Controllers\Owner\ServiceController;
use App\Http\Controllers\Owner\SearchController;
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

        // Owner notifications (new-business bell)
        Route::get('notifications', [\App\Http\Controllers\Owner\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [\App\Http\Controllers\Owner\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{notification}', [\App\Http\Controllers\Owner\NotificationController::class, 'read'])->name('notifications.read');

        // Global search
        Route::get('/search', [SearchController::class, 'index'])->name('search.index');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::resource('categories', CategoryController::class);
        Route::resource('service-categories', ServiceCategoryController::class)->except(['create', 'edit', 'show']);

        Route::post('companies/{company}/impersonate', [\App\Http\Controllers\Owner\ImpersonationController::class, 'start'])
            ->middleware('owner.can:companies.impersonate')
            ->name('companies.impersonate');
        Route::post('impersonation/stop', [\App\Http\Controllers\Owner\ImpersonationController::class, 'stop'])
            ->name('impersonation.stop');

        Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])
            ->middleware('owner.can:companies.manage')
            ->name('companies.update-status');
        Route::patch('companies/{company}/subscription', [CompanyController::class, 'updateSubscription'])
            ->middleware('owner.can:companies.manage')
            ->name('companies.update-subscription');
        Route::resource('companies', CompanyController::class)->only(['index', 'show']);
        Route::resource('companies', CompanyController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('owner.can:companies.manage');

        // Subscription plans
        Route::resource('plans', \App\Http\Controllers\Owner\PlanController::class)->only(['index']);
        Route::resource('plans', \App\Http\Controllers\Owner\PlanController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('owner.can:plans.manage');

        // Audit log (append-only, read-only)
        Route::get('audit-log', [\App\Http\Controllers\Owner\AuditLogController::class, 'index'])
            ->middleware('owner.can:audit-log.view')
            ->name('audit-log.index');

        // Billing
        Route::get('subscriptions', [\App\Http\Controllers\Owner\SubscriptionController::class, 'index'])
            ->middleware('owner.can:billing.view')
            ->name('subscriptions.index');
        Route::get('subscription-payments', [\App\Http\Controllers\Owner\SubscriptionPaymentController::class, 'index'])
            ->middleware('owner.can:billing.view')
            ->name('subscription-payments.index');
        Route::post('subscription-payments', [\App\Http\Controllers\Owner\SubscriptionPaymentController::class, 'store'])
            ->middleware('owner.can:billing.record-payment')
            ->name('subscription-payments.store');
        Route::put('subscription-payments/{payment}', [\App\Http\Controllers\Owner\SubscriptionPaymentController::class, 'update'])
            ->middleware('owner.can:billing.record-payment')
            ->name('subscription-payments.update');
        Route::patch('subscription-payments/{payment}/void', [\App\Http\Controllers\Owner\SubscriptionPaymentController::class, 'void'])
            ->middleware('owner.can:billing.void-payment')
            ->name('subscription-payments.void');

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

        // Reviews moderation
        Route::get('reviews', [\App\Http\Controllers\Owner\ReviewController::class, 'index'])
            ->middleware('owner.can:reviews.moderate')
            ->name('reviews.index');
        Route::patch('reviews/{review}/toggle-hidden', [\App\Http\Controllers\Owner\ReviewController::class, 'toggleHidden'])
            ->middleware('owner.can:reviews.moderate')
            ->name('reviews.toggle-hidden');

        // Broadcast announcements
        Route::get('announcements', [\App\Http\Controllers\Owner\AnnouncementController::class, 'index'])
            ->middleware('owner.can:notifications.send')
            ->name('announcements.index');
        Route::post('announcements', [\App\Http\Controllers\Owner\AnnouncementController::class, 'store'])
            ->middleware('owner.can:notifications.send')
            ->name('announcements.store');

        // Subscription coupons
        Route::resource('coupons', \App\Http\Controllers\Owner\CouponController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('owner.can:coupons.manage')
            ->parameters(['coupons' => 'coupon']);

        // Reports
        Route::get('reports/growth', [\App\Http\Controllers\Owner\ReportController::class, 'growth'])
            ->middleware('owner.can:reports.view')
            ->name('reports.growth');
        Route::get('reports/revenue', [\App\Http\Controllers\Owner\ReportController::class, 'revenue'])
            ->middleware('owner.can:reports.view')
            ->name('reports.revenue');

        // Cross-tenant read-only directories
        Route::get('customers', [\App\Http\Controllers\Owner\CustomerController::class, 'index'])
            ->middleware('owner.can:operations.view')
            ->name('customers.index');
        Route::get('invoices', [\App\Http\Controllers\Owner\InvoiceController::class, 'index'])
            ->middleware('owner.can:operations.view')
            ->name('invoices.index');

        // Locations
        Route::get('locations',                                   [LocationController::class, 'index'])->name('locations.index');
        Route::get('locations/governorates-by-country',          [LocationController::class, 'governoratesByCountry'])->name('locations.governorates-by-country');
        Route::middleware('owner.can:locations.manage')->group(function () {
            Route::post('locations/countries',                        [LocationController::class, 'storeCountry'])->name('locations.countries.store');
            Route::put('locations/countries/{country}',              [LocationController::class, 'updateCountry'])->name('locations.countries.update');
            Route::delete('locations/countries/{country}',           [LocationController::class, 'destroyCountry'])->name('locations.countries.destroy');
            Route::post('locations/governorates',                    [LocationController::class, 'storeGovernorate'])->name('locations.governorates.store');
            Route::put('locations/governorates/{governorate}',       [LocationController::class, 'updateGovernorate'])->name('locations.governorates.update');
            Route::delete('locations/governorates/{governorate}',    [LocationController::class, 'destroyGovernorate'])->name('locations.governorates.destroy');
            Route::post('locations/areas',                           [LocationController::class, 'storeArea'])->name('locations.areas.store');
            Route::put('locations/areas/{area}',                     [LocationController::class, 'updateArea'])->name('locations.areas.update');
            Route::delete('locations/areas/{area}',                  [LocationController::class, 'destroyArea'])->name('locations.areas.destroy');
        });
    });
});
