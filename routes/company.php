<?php

use App\Http\Controllers\Company\AppointmentController;
use App\Http\Controllers\Company\WaitlistController;
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
use App\Http\Controllers\Company\CustomerDebtController;
use App\Http\Controllers\Company\InventoryController;
use App\Http\Controllers\Company\RecurringExpenseController;
use App\Http\Controllers\Company\ReportController;
use App\Http\Controllers\Company\ProductCategoryController;
use App\Http\Controllers\Company\StockTransferController;
use App\Http\Controllers\Company\SearchController;
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

        // Global search
        Route::get('/search', [SearchController::class, 'index'])->name('search.index');

        // Staff & Services (all branches)
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');

        // Location AJAX
        Route::get('/locations/governorates', [LocationController::class, 'governorates'])->name('locations.governorates');
        Route::get('/locations/areas',        [LocationController::class, 'areas'])->name('locations.areas');

        // Service categories
        Route::resource('service-categories', ServiceCategoryController::class)
            ->except(['create', 'edit', 'show']);

        // Resources (rooms & equipment)
        Route::get(   'resources',            [\App\Http\Controllers\Company\ResourceController::class, 'index'])->name('resources.index');
        Route::post(  'resources',            [\App\Http\Controllers\Company\ResourceController::class, 'store'])->name('resources.store');
        Route::put(   'resources/{resource}', [\App\Http\Controllers\Company\ResourceController::class, 'update'])->name('resources.update');
        Route::delete('resources/{resource}', [\App\Http\Controllers\Company\ResourceController::class, 'destroy'])->name('resources.destroy');

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
        Route::post( 'services/{service}/duplicate',     [ServiceController::class, 'duplicate'])->name('services.duplicate');
        // Branch-scoped batch operations (must precede the resource route)
        Route::post('branches/{branch}/services/bulk',    [ServiceController::class, 'bulk'])->name('branches.services.bulk');
        Route::post('branches/{branch}/services/copy',    [ServiceController::class, 'copyToBranches'])->name('branches.services.copy');
        Route::post('branches/{branch}/services/reorder', [ServiceController::class, 'reorder'])->name('branches.services.reorder');
        Route::get( 'branches/{branch}/services/export',  [ServiceController::class, 'exportCsv'])->name('branches.services.export');
        Route::post('branches/{branch}/services/import',  [ServiceController::class, 'importCsv'])->name('branches.services.import');
        Route::resource('branches.services', ServiceController::class)->shallow()->except(['show']);
        // Service category reordering (drag-and-drop in the rail)
        Route::post('service-categories/reorder', [ServiceCategoryController::class, 'reorder'])->name('service-categories.reorder');

        // Employees (nested under branch, shallow)
        Route::get('employees/check-email', [EmployeeController::class, 'checkEmail'])->name('employees.check-email');
        Route::resource('branches.employees', EmployeeController::class)->shallow();

        // ── Finance module (plan-gated) ──
        Route::middleware('feature:finance')->group(function () {

        // Cash box — global (all branches)
        Route::get('cash', [CashController::class, 'globalIndex'])->name('cash.global');

        // Cash box (per branch)
        Route::get(   'branches/{branch}/cash',             [CashController::class, 'index'])->name('branches.cash.index');
        Route::post(  'branches/{branch}/cash',             [CashController::class, 'store'])->name('branches.cash.store');
        Route::post(  'branches/{branch}/cash/drawer/open',              [CashController::class, 'openDrawer'])->name('branches.cash.drawer.open');
        Route::put(   'branches/{branch}/cash/drawer/{session}/close', [CashController::class, 'closeDrawer'])->name('branches.cash.drawer.close');
        Route::put(   'branches/{branch}/cash/drawer/{session}/reconcile', [CashController::class, 'reconcileDrawer'])->name('branches.cash.drawer.reconcile');
        Route::put(   'branches/{branch}/cash/drawer/{session}/reopen',    [CashController::class, 'reopenDrawer'])->name('branches.cash.drawer.reopen');
        Route::put(   'branches/{branch}/cash/drawer/{session}/void',      [CashController::class, 'voidDrawer'])->name('branches.cash.drawer.void');
        Route::put(   'branches/{branch}/cash/drawer/{session}/notes',     [CashController::class, 'updateDrawerNotes'])->name('branches.cash.drawer.update-notes');
        Route::delete('branches/{branch}/cash/destroy-selected', [CashController::class, 'destroySelected'])->name('branches.cash.destroy-selected');
        Route::delete('branches/{branch}/cash/destroy-all',      [CashController::class, 'destroyAll'])->name('branches.cash.destroy-all');
        Route::get(   'branches/{branch}/cash/drawer/archive',   [CashController::class, 'drawerArchive'])->name('branches.cash.drawer.archive');
        Route::get(   'branches/{branch}/cash/drawer/{session}/expected', [CashController::class, 'expectedBalance'])->name('branches.cash.drawer.expected');
        Route::get(   'branches/{branch}/cash/export/csv',  [CashController::class, 'exportCsv'])->name('branches.cash.export.csv');
        Route::get(   'branches/{branch}/cash/export/pdf',  [CashController::class, 'exportPdf'])->name('branches.cash.export.pdf');
        Route::post(  'branches/{branch}/cash/overpayment', [CashController::class, 'setOverpayment'])->name('branches.cash.overpayment');
        Route::put(   'branches/{branch}/cash/{payment}',   [CashController::class, 'update'])->name('branches.cash.update');
        Route::delete('branches/{branch}/cash/{payment}',   [CashController::class, 'destroy'])->name('branches.cash.destroy');

        }); // end feature:finance (cash)

        // ── Payroll module (plan-gated) ──
        Route::middleware('feature:payroll')->group(function () {

        // Payroll reports
        Route::get('payroll',                            [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('payroll/export',                     [PayrollController::class, 'exportCsv'])->name('payroll.export');
        Route::post('payroll/pay-all',                   [PayrollController::class, 'payAll'])->name('payroll.pay-all');
        Route::get('employees/{employee}/payroll',       [PayrollController::class, 'show'])->name('employees.payroll');
        Route::post('employees/{employee}/payroll/pay',  [PayrollController::class, 'markAsPaid'])->name('employees.payroll.pay');

        // Employee deductions
        Route::get('deductions',                             [DeductionController::class, 'globalIndex'])->name('deductions.index');
        Route::get('employees/{employee}/deductions',        [DeductionController::class, 'index'])->name('employees.deductions.index');
        Route::get('employees/{employee}/deductions/create', [DeductionController::class, 'create'])->name('employees.deductions.create');
        Route::post('employees/{employee}/deductions',       [DeductionController::class, 'store'])->name('employees.deductions.store');
        Route::delete('deductions/{deduction}',              [DeductionController::class, 'destroy'])->name('deductions.destroy');

        // Salary advances
        Route::get(   'advances',           [\App\Http\Controllers\Company\AdvanceController::class, 'index'])->name('advances.index');
        Route::post(  'advances',           [\App\Http\Controllers\Company\AdvanceController::class, 'store'])->name('advances.store');
        Route::delete('advances/{advance}', [\App\Http\Controllers\Company\AdvanceController::class, 'destroy'])->name('advances.destroy');

        }); // end feature:payroll

        // ── Leaves module (plan-gated) ──
        Route::middleware('feature:leaves')->group(function () {

        // Employee leaves
        Route::get('team-calendar', [EmployeeLeaveController::class, 'calendar'])->name('team-calendar');
        Route::get('employee-leaves', [EmployeeLeaveController::class, 'index'])->name('employee-leaves.index');
        Route::get('employees/{employee}/leaves/create', [EmployeeLeaveController::class, 'create'])->name('employee-leaves.create');
        Route::post('employees/{employee}/leaves', [EmployeeLeaveController::class, 'store'])->name('employee-leaves.store');
        Route::patch('employee-leaves/{employeeLeave}/status', [EmployeeLeaveController::class, 'updateStatus'])->name('employee-leaves.update-status');
        Route::delete('employee-leaves/{employeeLeave}', [EmployeeLeaveController::class, 'destroy'])->name('employee-leaves.destroy');

        // Public holidays
        Route::get(   'holidays',           [\App\Http\Controllers\Company\HolidayController::class, 'index'])->name('holidays.index');
        Route::post(  'holidays',           [\App\Http\Controllers\Company\HolidayController::class, 'store'])->name('holidays.store');
        Route::delete('holidays/{holiday}', [\App\Http\Controllers\Company\HolidayController::class, 'destroy'])->name('holidays.destroy');

        }); // end feature:leaves

        // ── Attendance module (plan-gated) ──
        Route::middleware('feature:attendance')->group(function () {

        // Attendance
        Route::get( 'attendance',                              [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance',                              [AttendanceController::class, 'store'])->name('attendance.store');
        Route::put( 'attendance/{attendance_record}/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');
        Route::put( 'attendance/{attendance_record}',          [AttendanceController::class, 'update'])->name('attendance.update');
        Route::post('attendance/mark-absent',                  [AttendanceController::class, 'markAbsent'])->name('attendance.mark-absent');
        Route::post('attendance/{attendance_record}/suggest-deduction', [AttendanceController::class, 'storeSuggestedDeduction'])->name('attendance.suggest-deduction');
        Route::get( 'attendance/report',                       [AttendanceController::class, 'report'])->name('attendance.report');

        }); // end feature:attendance

        // Offboarding
        Route::post('employees/{employee}/offboard',  [EmployeeController::class, 'offboard'])->name('employees.offboard');
        Route::post('employees/{employee}/reinstate', [EmployeeController::class, 'reinstate'])->name('employees.reinstate');

        // Profile
        Route::get('/profile',  [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile',  [ProfileController::class, 'update'])->name('profile.update');

        // Appointments
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::get('appointments/branch-data',    [AppointmentController::class, 'branchData'])->name('appointments.branch-data');
        Route::get('appointments/calendar-events',[AppointmentController::class, 'calendarEvents'])->name('appointments.calendar-events');
        Route::get('appointments/staff-events',   [AppointmentController::class, 'staffEvents'])->name('appointments.staff-events');
        Route::get('appointments/stats',          [AppointmentController::class, 'stats'])->name('appointments.stats');
        Route::patch('appointments/{appointment}/tip', [AppointmentController::class, 'updateTip'])->name('appointments.tip');
        Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::post('appointments/quick', [AppointmentController::class, 'quickStore'])->name('appointments.quick-store');
        Route::post('appointments/quick-group', [AppointmentController::class, 'quickGroupStore'])->name('appointments.quick-group-store');
        Route::get('appointments/checkout-data', [AppointmentController::class, 'checkoutData'])->name('appointments.checkout-data');
        Route::get('appointments/{appointment}/details-json', [AppointmentController::class, 'detailsJson'])->name('appointments.details-json');
        Route::post('appointments/{appointment}/checkout', [AppointmentController::class, 'checkout'])->name('appointments.checkout');
        Route::resource('appointments', AppointmentController::class)->only(['index', 'show', 'create', 'store']);

        // Blocked times (Fresha-style "block time")
        Route::get('blocked-times',  [AppointmentController::class, 'listBlockedTimes'])->name('blocked-times.index');
        Route::post('blocked-times', [AppointmentController::class, 'storeBlockedTime'])->name('blocked-times.store');
        Route::delete('blocked-times/{blockedTime}', [AppointmentController::class, 'destroyBlockedTime'])->name('blocked-times.destroy');

        // Waitlist (reception queue, plan-gated)
        Route::middleware('feature:waitlist')->group(function () {
            Route::get('waitlist',  [WaitlistController::class, 'index'])->name('waitlist.index');
            Route::post('waitlist', [WaitlistController::class, 'store'])->name('waitlist.store');
            Route::patch('waitlist/{waitlistEntry}', [WaitlistController::class, 'resolve'])->name('waitlist.resolve');
            Route::post('waitlist/{waitlistEntry}/promote', [WaitlistController::class, 'promote'])->name('waitlist.promote');
        });

        // ── Invoices (finance, plan-gated) ──
        Route::middleware('feature:finance')->group(function () {

        // Invoices
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'recordPayment'])->name('invoices.pay');
        Route::patch('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('appointments/{appointment}/invoice', [InvoiceController::class, 'storeFromAppointment'])->name('appointments.invoice.store');

        }); // end feature:finance (invoices)

        // Customers
        Route::get( 'customers',              [CustomerController::class, 'index'])->name('customers.index');
        Route::post('customers',              [CustomerController::class, 'store'])->name('customers.store');
        Route::post('customers/import',       [CustomerController::class, 'import'])->name('customers.import');
        Route::get( 'customers/export/csv',   [CustomerController::class, 'exportCsv'])->name('customers.export.csv');
        Route::get( 'customers/search/json',  [CustomerController::class, 'searchJson'])->name('customers.search-json');
        Route::get( 'customers/{customer}',   [CustomerController::class, 'show'])->name('customers.show');
        Route::put( 'customers/{customer}',      [CustomerController::class, 'update'])->name('customers.update');
        Route::put( 'customers/{customer}/tag', [CustomerController::class, 'updateTag'])->name('customers.update-tag');
        Route::put( 'customers/{customer}/ban', [CustomerController::class, 'toggleBan'])->name('customers.toggle-ban');
        Route::delete('customers/{customer}',   [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::put('customers/{customer}/branch-note/{branch}', [CustomerController::class, 'updateBranchNote'])->name('customers.branch-note');

        // Treatment Plans (plan-gated)
        Route::middleware('feature:treatment_plans')->group(function () {
            Route::post('customers/{customer}/treatment-plans',        [\App\Http\Controllers\Company\TreatmentPlanController::class, 'store'])->name('customers.treatment-plans.store');
            Route::put( 'treatment-plan-sessions/{session}',           [\App\Http\Controllers\Company\TreatmentPlanController::class, 'updateSession'])->name('treatment-plan-sessions.update');
            Route::delete('treatment-plans/{treatmentPlan}',           [\App\Http\Controllers\Company\TreatmentPlanController::class, 'destroy'])->name('treatment-plans.destroy');
        });

        // Loyalty Points (plan-gated)
        Route::post('customers/{customer}/loyalty-points',         [\App\Http\Controllers\Company\LoyaltyPointController::class, 'adjust'])->name('customers.loyalty.adjust')->middleware('feature:loyalty');

        // Customer Communications
        Route::post('customers/{customer}/communications',         [\App\Http\Controllers\Company\CustomerCommunicationController::class, 'store'])->name('customers.communications.store');

        // Customer Debts (finance, plan-gated)
        Route::middleware('feature:finance')->group(function () {
            Route::get('debts',                           [CustomerDebtController::class, 'index'])->name('debts.index');
            Route::post('debts',                          [CustomerDebtController::class, 'store'])->name('debts.store');
            Route::get('debts/{debt}',                    [CustomerDebtController::class, 'show'])->name('debts.show');
            Route::post('debts/{debt}/pay',               [CustomerDebtController::class, 'recordPayment'])->name('debts.pay');
            Route::put('debts/{debt}/waive',              [CustomerDebtController::class, 'waive'])->name('debts.waive');
        });

        // ── Inventory module (plan-gated) ──
        Route::middleware('feature:inventory')->group(function () {

        // Inventory — static routes (must come before {product} wildcard)
        Route::get('inventory',                          [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/create',                   [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('inventory',                         [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('inventory/stock',                    [InventoryController::class, 'stock'])->name('inventory.stock');
        Route::get('inventory/movements',                [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::delete('inventory/movements',             [InventoryController::class, 'deleteMovements'])->name('inventory.movements.delete');
        Route::get('inventory/transfers',                [StockTransferController::class, 'index'])->name('inventory.transfers.index');
        Route::get('inventory/transfers/create',         [StockTransferController::class, 'create'])->name('inventory.transfers.create');
        Route::post('inventory/transfers',               [StockTransferController::class, 'store'])->name('inventory.transfers.store');

        // Inventory — Stock actions
        Route::post('branches/{branch}/stock/add',       [InventoryController::class, 'addStock'])->name('inventory.stock.add');
        Route::post('branches/{branch}/stock/remove',    [InventoryController::class, 'removeStock'])->name('inventory.stock.remove');
        Route::post('branches/{branch}/stock/adjust',    [InventoryController::class, 'adjustStock'])->name('inventory.stock.adjust');
        Route::post('branches/{branch}/stock/sell',      [InventoryController::class, 'sell'])->name('inventory.stock.sell');

        // Inventory — Product wildcard routes (must come after all static routes)
        Route::get('inventory/{product}',                [InventoryController::class, 'show'])->name('inventory.show');
        Route::get('inventory/{product}/edit',           [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('inventory/{product}',                [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{product}',             [InventoryController::class, 'destroy'])->name('inventory.destroy');

        // Inventory — Transfers with wildcard
        Route::put('inventory/transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('inventory.transfers.receive');
        Route::put('inventory/transfers/{transfer}/cancel',  [StockTransferController::class, 'cancel'])->name('inventory.transfers.cancel');

        // Inventory — Product Categories
        Route::get('product-categories',                     [ProductCategoryController::class, 'index'])->name('product-categories.index');
        Route::post('product-categories',                    [ProductCategoryController::class, 'store'])->name('product-categories.store');
        Route::put('product-categories/{productCategory}',   [ProductCategoryController::class, 'update'])->name('product-categories.update');
        Route::delete('product-categories/{productCategory}',[ProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

        }); // end feature:inventory

        // Recurring Expenses (finance, plan-gated)
        Route::middleware('feature:finance')->group(function () {
            Route::get('recurring-expenses',                             [RecurringExpenseController::class, 'index'])->name('recurring-expenses.index');
            Route::post('recurring-expenses',                            [RecurringExpenseController::class, 'store'])->name('recurring-expenses.store');
            Route::put('recurring-expenses/{recurringExpense}/toggle',   [RecurringExpenseController::class, 'toggle'])->name('recurring-expenses.toggle');
            Route::delete('recurring-expenses/{recurringExpense}',       [RecurringExpenseController::class, 'destroy'])->name('recurring-expenses.destroy');
        });

        // ── Reports & activity log (plan-gated) ──
        Route::middleware('feature:reports')->group(function () {
            // Reports
            Route::get('reports/profit-loss',           [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
            Route::get('reports/employee-performance',  [ReportController::class, 'employeePerformance'])->name('reports.employee-performance');

            // Activity log
            Route::get(   'activity-log',          [ActivityLogController::class, 'index'])->name('activity-log.index');
            Route::delete('activity-log/selected', [ActivityLogController::class, 'destroySelected'])->name('activity-log.destroy-selected');
            Route::delete('activity-log/all',      [ActivityLogController::class, 'destroyAll'])->name('activity-log.destroy-all');
        });

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

