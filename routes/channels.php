<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Company;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Owner/company/employee listening on branch channel
Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    // Allow Company (business account) that owns that branch
    if ($user instanceof \App\Models\Company) {
        return Branch::where('id', $branchId)
            ->where('company_id', $user->id)
            ->exists();
    }
    // Allow Owner model
    if ($user instanceof \App\Models\Owner) {
        return Branch::where('id', $branchId)
            ->whereHas('company', fn($q) => $q->where('owner_id', $user->id))
            ->exists();
    }
    // Allow Employee of that branch
    if ($user instanceof Employee) {
        return $user->branch_id == $branchId;
    }
    return false;
});

// Employee personal channel
Broadcast::channel('employee.{employeeId}', function ($user, $employeeId) {
    return $user instanceof Employee && $user->id == $employeeId;
});
