<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanSession;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreatmentPlanController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'branch_id'      => ['required', 'exists:branches,id'],
            'total_amount'   => ['required', 'numeric', 'min:1'],
            'currency'       => ['required', 'string', 'size:3'],
            'session_count'  => ['required', 'integer', 'min:1', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = TreatmentPlan::create([
            'customer_id'  => $customer->id,
            'branch_id'    => $data['branch_id'],
            'title'        => $data['title'],
            'total_amount' => $data['total_amount'],
            'currency'     => $data['currency'],
            'notes'        => $data['notes'] ?? null,
        ]);

        $perSession = round((float) $data['total_amount'] / (int) $data['session_count'], 2);
        $remainder  = round((float) $data['total_amount'] - ($perSession * (int) $data['session_count']), 2);

        for ($i = 1; $i <= (int) $data['session_count']; $i++) {
            $amount = $perSession;
            if ($i === (int) $data['session_count']) {
                $amount = round($perSession + $remainder, 2);
            }
            $plan->sessions()->create([
                'session_number' => $i,
                'amount_due'     => $amount,
                'currency'       => $data['currency'],
            ]);
        }

        Auditor::log("Created treatment plan: {$data['title']} — {$data['total_amount']} {$data['currency']} ({$data['session_count']} sessions)", $plan);

        return back()->with('success', __('Treatment plan created.'));
    }

    public function updateSession(Request $request, TreatmentPlanSession $session): RedirectResponse
    {
        $data = $request->validate([
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'in:pending,completed,skipped'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $session->only(['amount_paid', 'status', 'notes']);

        $session->update([
            'amount_paid' => $data['amount_paid'] ?? $session->amount_paid,
            'status'      => $data['status'],
            'notes'       => $data['notes'] ?? $session->notes,
            'completed_at'=> $data['status'] === 'completed' ? now() : $session->completed_at,
        ]);

        $plan = $session->plan;
        $allDone = $plan->sessions()->whereIn('status', ['completed', 'skipped'])->count() === $plan->sessions()->count();
        if ($allDone && $plan->isActive()) {
            $plan->update(['status' => 'completed']);
        }

        Auditor::logChange("Updated session #{$session->session_number} of plan #{$plan->id}", $session, $old, $session->only(['amount_paid', 'status', 'notes']));

        return back()->with('success', __('Session updated.'));
    }

    public function destroy(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $treatmentPlan->update(['status' => 'cancelled']);

        Auditor::log("Cancelled treatment plan: {$treatmentPlan->title}", $treatmentPlan);

        return back()->with('success', __('Treatment plan cancelled.'));
    }
}
