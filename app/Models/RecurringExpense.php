<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];

    public const PRESET_TITLES = [
        'rent' => 'Rent',
        'electricity' => 'Electricity',
        'water' => 'Water',
        'internet' => 'Internet',
        'insurance' => 'Insurance',
        'supplies' => 'Supplies',
        'other' => 'Other',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'title', 'category',
        'amount', 'currency', 'frequency',
        'next_due_date', 'last_run_date', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_due_date' => 'date',
            'last_run_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function isDue(): bool
    {
        return $this->is_active && $this->next_due_date->lte(now());
    }

    public function advanceNextDue(): void
    {
        $this->next_due_date = match ($this->frequency) {
            'daily' => $this->next_due_date->addDay(),
            'weekly' => $this->next_due_date->addWeek(),
            'yearly' => $this->next_due_date->addYear(),
            default => $this->next_due_date->addMonth(),
        };
        $this->last_run_date = now()->toDateString();
        $this->save();
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
