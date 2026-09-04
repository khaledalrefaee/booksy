<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CustomerLoyalty;
use App\Models\LoyaltyPointLog;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for loyalty points. Balances live per (customer,
 * branch) in customer_loyalty; customers.loyalty_points mirrors the grand total
 * for backward-compatible summaries; every change is written to loyalty_point_logs.
 */
class LoyaltyService
{
    /** Add points to a customer's balance at a branch. No-op for <= 0. */
    public function earn(Customer $customer, int $branchId, int $points, string $reason, ?string $refType = null, ?int $refId = null): void
    {
        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($customer, $branchId, $points, $reason, $refType, $refId) {
            $row = CustomerLoyalty::firstOrCreate(
                ['customer_id' => $customer->id, 'branch_id' => $branchId],
                ['points' => 0]
            );
            $row->increment('points', $points);
            Customer::whereKey($customer->id)->increment('loyalty_points', $points);

            LoyaltyPointLog::create([
                'customer_id'    => $customer->id,
                'branch_id'      => $branchId,
                'points'         => $points,
                'reason'         => $reason,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'created_at'     => now(),
            ]);
        });
    }

    /**
     * Award points for a completed appointment based on the branch's loyalty
     * settings. Idempotent — never awards twice for the same appointment.
     */
    public function earnForAppointment(Appointment $appointment): void
    {
        if (! $appointment->customer_id || ! $appointment->branch_id) {
            return;
        }

        $already = LoyaltyPointLog::where('reference_type', Appointment::class)
            ->where('reference_id', $appointment->id)
            ->where('points', '>', 0)
            ->exists();
        if ($already) {
            return;
        }

        $branch = $appointment->branch;
        if (! $branch) {
            return;
        }

        $points = (int) ($branch->loyalty_points_per_visit ?? 0);

        $perUnit = (int) ($branch->loyalty_points_per_currency_unit ?? 0);
        if ($perUnit > 0) {
            $points += (int) floor(((float) $appointment->total_price) / $perUnit);
        }

        if ($points <= 0) {
            return;
        }

        $this->earn(
            $appointment->customer,
            $branch->id,
            $points,
            __('Points for a completed appointment'),
            Appointment::class,
            $appointment->id,
        );
    }

    public function balance(int $customerId, int $branchId): int
    {
        return (int) (CustomerLoyalty::where('customer_id', $customerId)
            ->where('branch_id', $branchId)->value('points') ?? 0);
    }

    public function canRedeem(Customer $customer, LoyaltyReward $reward): bool
    {
        return $reward->is_active
            && $this->balance($customer->id, $reward->branch_id) >= $reward->points_cost;
    }

    /**
     * Spend points on a reward. Returns the redemption voucher, or null when the
     * balance is insufficient (checked under a row lock to avoid double-spend).
     */
    public function redeem(Customer $customer, LoyaltyReward $reward, ?int $employeeId = null, ?int $appointmentId = null): ?LoyaltyRedemption
    {
        return DB::transaction(function () use ($customer, $reward, $employeeId, $appointmentId) {
            $row = CustomerLoyalty::where('customer_id', $customer->id)
                ->where('branch_id', $reward->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $row || $row->points < $reward->points_cost) {
                return null;
            }

            $row->decrement('points', $reward->points_cost);
            Customer::whereKey($customer->id)->decrement('loyalty_points', $reward->points_cost);

            LoyaltyPointLog::create([
                'customer_id'    => $customer->id,
                'branch_id'      => $reward->branch_id,
                'points'         => -$reward->points_cost,
                'reason'         => __('Redeemed: :name', ['name' => $reward->name]),
                'reference_type' => LoyaltyReward::class,
                'reference_id'   => $reward->id,
                'created_at'     => now(),
            ]);

            return LoyaltyRedemption::create([
                'customer_id'             => $customer->id,
                'branch_id'               => $reward->branch_id,
                'loyalty_reward_id'       => $reward->id,
                'reward_name'             => $reward->name,
                'points_spent'            => $reward->points_cost,
                'type'                    => $reward->type,
                'service_id'              => $reward->service_id,
                'discount_percent'        => $reward->discount_percent,
                'appointment_id'          => $appointmentId,
                'redeemed_by_employee_id' => $employeeId,
                'status'                  => 'issued',
            ]);
        });
    }
}
