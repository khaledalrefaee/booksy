<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BookingPolicy;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookingPolicyController extends Controller
{
    private function company(): Company
    {
        /** @var Company */
        return Auth::guard('company')->user();
    }

    public function edit(): View
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();

        // Company-wide default (create an in-memory one from defaults if missing)
        $companyPolicy = $company->bookingPolicies()->whereNull('branch_id')->first()
            ?? new BookingPolicy(BookingPolicy::defaults());

        // Per-branch overrides keyed by branch id (fall back to defaults for display)
        $branchPolicies = [];
        foreach ($branches as $branch) {
            $branchPolicies[$branch->id] = $company->bookingPolicies()
                ->where('branch_id', $branch->id)->first()
                ?? new BookingPolicy(BookingPolicy::defaults());
        }

        return view('company.booking-policy.edit', [
            'company'        => $company,
            'branches'       => $branches,
            'companyPolicy'  => $companyPolicy,
            'branchPolicies' => $branchPolicies,
            'mode'           => $company->booking_policy_mode ?? 'unified',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'mode'                 => ['required', 'in:unified,per_branch'],
            // Unified block
            'unified'              => ['required_if:mode,unified', 'array'],
            // Per-branch block: keyed by branch id
            'branch'               => ['required_if:mode,per_branch', 'array'],
        ]);

        $company->update(['booking_policy_mode' => $data['mode']]);

        // The company-wide default is always saved (from the "unified" block —
        // in per-branch mode it doubles as the fallback baseline).
        $baseline = $request->input('mode') === 'unified'
            ? $request->input('unified', [])
            : $request->input('unified', $request->input('branch.'.($company->branches()->value('id')), []));

        $this->savePolicy($company, null, $baseline);

        if ($data['mode'] === 'per_branch') {
            foreach ($request->input('branch', []) as $branchId => $values) {
                // Guard: only branches that belong to this company
                if ($company->branches()->whereKey($branchId)->exists()) {
                    $this->savePolicy($company, (int) $branchId, $values);
                }
            }
        }

        return back()->with('success', __('Booking policy saved'));
    }

    /**
     * Normalise + persist one policy row (checkbox fields default to false when absent).
     */
    private function savePolicy(Company $company, ?int $branchId, array $input): void
    {
        $clean = [
            'cancellation_window_hours'   => (int) ($input['cancellation_window_hours'] ?? 24),
            'late_grace_minutes'          => (int) ($input['late_grace_minutes'] ?? 15),
            'late_action'                 => in_array($input['late_action'] ?? '', ['staff_decides', 'auto_cancel']) ? $input['late_action'] : 'staff_decides',
            'reminder_channel'            => in_array($input['reminder_channel'] ?? '', ['whatsapp', 'sms']) ? $input['reminder_channel'] : 'whatsapp',
            'reminder_on_booking'         => ! empty($input['reminder_on_booking']),
            'reminder_24h'                => ! empty($input['reminder_24h']),
            'reminder_3h'                 => ! empty($input['reminder_3h']),
            'require_confirmation'        => ! empty($input['require_confirmation']),
            'confirmation_deadline_hours' => (int) ($input['confirmation_deadline_hours'] ?? 6),
            'protection_enabled'          => ! empty($input['protection_enabled']),
            'offense_threshold'           => max(1, (int) ($input['offense_threshold'] ?? 2)),
            'offense_window_days'         => max(1, (int) ($input['offense_window_days'] ?? 60)),
            'action_alert_staff'          => ! empty($input['action_alert_staff']),
            'action_manual_confirm'       => ! empty($input['action_manual_confirm']),
            'deposit_enabled'             => ! empty($input['deposit_enabled']),
            'deposit_type'                => in_array($input['deposit_type'] ?? '', ['fixed', 'percent']) ? $input['deposit_type'] : 'fixed',
            'deposit_amount'              => round((float) ($input['deposit_amount'] ?? 0), 2),
            'deposit_scope'               => in_array($input['deposit_scope'] ?? '', ['at_risk', 'new', 'all']) ? $input['deposit_scope'] : 'at_risk',
            'msg_confirm'                 => $this->cleanTemplate($input['msg_confirm'] ?? null),
            'msg_reminder_24h'            => $this->cleanTemplate($input['msg_reminder_24h'] ?? null),
            'msg_reminder_3h'             => $this->cleanTemplate($input['msg_reminder_3h'] ?? null),
            'msg_unconfirmed'             => $this->cleanTemplate($input['msg_unconfirmed'] ?? null),
        ];

        $company->bookingPolicies()->updateOrCreate(
            ['branch_id' => $branchId],
            $clean
        );
    }

    private function cleanTemplate(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;
        return $value === '' ? null : $value;
    }
}
