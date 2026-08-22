<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * L4 — a single per-branch permission override for one employee.
 * effect: 'grant' | 'deny'. See branch_employee_permission migration.
 */
class BranchEmployeePermission extends Model
{
    protected $table = 'branch_employee_permission';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'permission_id',
        'effect',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
