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
use App\Http\Controllers\Company\LocationController;
use App\Http\Controllers\Company\StaffController;
use App\Http\Controllers\Company\ProfileController;
use App\Http\Controllers\Company\CashController;
use App\Http\Controllers\Company\PayrollController;
use App\Http\Controllers\Company\WorkingHoursController;
use App\Http\Controllers\Company\InvoiceController;
use App\Http\Controllers\Company\ActivityLogController;
use App\Http\Controllers\Company\CustomerController;
use App\Http\Controllers\Company\AttendanceController;
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
        Route::get('/dashboard/chart/month', [DashboardController::class, 'monthChart'])->name('dashboard.chart.month');

        // Staff & Services (all branches)
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');

        // Location AJAX
        Route::get('/locations/governorates', [LocationController::class, 'governorates'])->name('locations.governorates');
        Route::get('/locations/areas',        [LocationController::class, 'areas'])->name('locations.areas');

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

        // Cash box — global (all branches)
        Route::get('cash', [CashController::class, 'globalIndex'])->name('cash.global');

        // Cash box (per branch)
        Route::get(   'branches/{branch}/cash',             [CashController::class, 'index'])->name('branches.cash.index');
        Route::post(  'branches/{branch}/cash',             [CashController::class, 'store'])->name('branches.cash.store');
        Route::post(  'branches/{branch}/cash/drawer/open',              [CashController::class, 'openDrawer'])->name('branches.cash.drawer.open');
        Route::put(   'branches/{branch}/cash/drawer/{session}/close', [CashController::class, 'closeDrawer'])->name('branches.cash.drawer.close');
        Route::put(   'branches/{branch}/cash/drawer/{session}/reconcile', [CashController::class, 'reconcileDrawer'])->name('branches.cash.drawer.reconcile');
        Route::get(   'branches/{branch}/cash/export/csv',  [CashController::class, 'exportCsv'])->name('branches.cash.export.csv');
        Route::get(   'branches/{branch}/cash/export/pdf',  [CashController::class, 'exportPdf'])->name('branches.cash.export.pdf');
        Route::post(  'branches/{branch}/cash/overpayment', [CashController::class, 'setOverpayment'])->name('branches.cash.overpayment');
        Route::put(   'branches/{branch}/cash/{payment}',   [CashController::class, 'update'])->name('branches.cash.update');
        Route::delete('branches/{branch}/cash/{payment}',   [CashController::class, 'destroy'])->name('branches.cash.destroy');

        // Payroll reports
        Route::get('payroll',                        [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('employees/{employee}/payroll',   [PayrollController::class, 'show'])->name('employees.payroll');

        // Employee deductions
        Route::get('deductions',                             [DeductionController::class, 'globalIndex'])->name('deductions.index');
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

        // Attendance
        Route::get( 'attendance',                              [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance',                              [AttendanceController::class, 'store'])->name('attendance.store');
        Route::put( 'attendance/{attendance_record}/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');
        Route::post('attendance/mark-absent',                  [AttendanceController::class, 'markAbsent'])->name('attendance.mark-absent');
        Route::get( 'attendance/report',                       [AttendanceController::class, 'report'])->name('attendance.report');

        // Profile
        Route::get('/profile',  [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile',  [ProfileController::class, 'update'])->name('profile.update');

        // Appointments
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::get('appointments/branch-data',    [AppointmentController::class, 'branchData'])->name('appointments.branch-data');
        Route::get('appointments/calendar-events',[AppointmentController::class, 'calendarEvents'])->name('appointments.calendar-events');
        Route::get('appointments/staff-events',   [AppointmentController::class, 'staffEvents'])->name('appointments.staff-events');
        Route::resource('appointments', AppointmentController::class)->only(['index', 'show', 'create', 'store']);

        // Invoices
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
        Route::patch('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('appointments/{appointment}/invoice', [InvoiceController::class, 'storeFromAppointment'])->name('appointments.invoice.store');

        // Customers
        Route::get( 'customers',              [CustomerController::class, 'index'])->name('customers.index');
        Route::post('customers',              [CustomerController::class, 'store'])->name('customers.store');
        Route::post('customers/import',       [CustomerController::class, 'import'])->name('customers.import');
        Route::get( 'customers/{customer}',   [CustomerController::class, 'show'])->name('customers.show');

        // Activity log
        Route::get(   'activity-log',          [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::delete('activity-log/selected', [ActivityLogController::class, 'destroySelected'])->name('activity-log.destroy-selected');
        Route::delete('activity-log/all',      [ActivityLogController::class, 'destroyAll'])->name('activity-log.destroy-all');

        // Notifications
        Route::post('notifications/{notification}/read', function (\App\Models\StaffNotification $notification) {
            $notification->update(['read_at' => now()]);
            return response()->json(['ok' => true]);
        })->name('notifications.read');
        Route::post('notifications/read-all', function () {
            \App\Models\StaffNotification::where('company_id', Auth::guard('company')->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            return back();
        })->name('notifications.read-all');
    });
});

