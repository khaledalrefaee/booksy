<?php

/*
 * Company Workspace — "appointments" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the appointments module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\AppointmentsController;
use Illuminate\Support\Facades\Route;

Route::get('appointments', [AppointmentsController::class, 'index'])->name('appointments');
Route::get('appointments/{appointment}/detail', [AppointmentsController::class, 'detail'])->name('appointments.detail');
Route::patch('appointments/{appointment}/status', [AppointmentsController::class, 'updateStatus'])->name('appointments.status');
Route::patch('appointments/waitlist/{waitlistEntry}/resolve', [AppointmentsController::class, 'resolveWaitlist'])->name('appointments.waitlist.resolve');
Route::delete('appointments/blocked/{blockedTime}', [AppointmentsController::class, 'destroyBlocked'])->name('appointments.blocked.destroy');
