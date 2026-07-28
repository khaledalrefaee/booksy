<?php

namespace App\Console\Commands;

use App\Models\BranchPayment;
use App\Models\RecurringExpense;
use App\Support\Auditor;
use Illuminate\Console\Command;

class ProcessRecurringExpenses extends Command
{
    protected $signature = 'booksy:process-recurring-expenses';
    protected $description = 'Process due recurring expenses and record them in cash register';

    public function handle(): int
    {
        $due = RecurringExpense::where('is_active', true)
            ->where('next_due_date', '<=', now()->toDateString())
            ->with('branch')
            ->get();

        $count = 0;

        foreach ($due as $expense) {
            BranchPayment::create([
                'company_id' => $expense->company_id,
                'branch_id' => $expense->branch_id,
                'type' => 'expense',
                'category' => $expense->category,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'payment_method' => 'cash',
                'notes' => $expense->title . ($expense->notes ? " — {$expense->notes}" : ''),
                'paid_at' => now(),
            ]);

            Auditor::log("Auto-posted recurring expense: {$expense->title} — {$expense->amount} {$expense->currency}", $expense);

            $expense->advanceNextDue();
            $count++;
        }

        $this->info("Processed {$count} recurring expenses.");

        return self::SUCCESS;
    }
}
