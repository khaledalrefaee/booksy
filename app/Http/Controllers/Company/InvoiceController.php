<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $query = Invoice::query()
            ->where('company_id', $company->id)
            ->with('branch')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q
                ->where('invoice_number', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('customer_phone', 'like', "%$s%")
            );
        }

        $invoices = $query->paginate(20)->withQueryString();
        $branches = $company->branches()->orderBy('sort_order')->get();

        return view('company.invoices.index', compact('invoices', 'branches'));
    }

    public function show(Invoice $invoice): View
    {
        abort_unless($invoice->company_id === $this->company()->id, 403);

        $invoice->load(['branch', 'items', 'appointment']);

        return view('company.invoices.show', compact('invoice'));
    }

    public function print(Invoice $invoice): View
    {
        abort_unless($invoice->company_id === $this->company()->id, 403);

        $invoice->load(['branch', 'items', 'appointment', 'company']);

        return view('company.invoices.print', compact('invoice'));
    }

    /**
     * Create invoice from appointment (manual trigger).
     */
    public function storeFromAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        $company = $this->company();
        abort_unless($appointment->company_id === $company->id, 403);

        if ($appointment->invoice()->exists()) {
            return redirect()->route('company.invoices.show', $appointment->invoice)
                ->with('info', __('Invoice already exists for this appointment.'));
        }

        $invoice = DB::transaction(function () use ($appointment, $company, $request) {
            return static::buildInvoiceFromAppointment($appointment, $company);
        });

        Auditor::log("Created invoice {$invoice->invoice_number}", $invoice);

        return redirect()->route('company.invoices.show', $invoice)
            ->with('success', __('Invoice created successfully.'));
    }

    /**
     * Update invoice status / payment.
     */
    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'status'         => ['required', 'in:draft,issued,paid,partial,refunded,void'],
            'payment_method' => ['nullable', 'in:cash,card,transfer,mixed'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $old = $invoice->only('status', 'payment_method');

        $invoice->update([
            'status'         => $data['status'],
            'payment_method' => $data['payment_method'] ?? $invoice->payment_method,
            'notes'          => $data['notes'] ?? $invoice->notes,
            'paid_at'        => $data['status'] === 'paid' ? now() : $invoice->paid_at,
            'issued_at'      => in_array($data['status'], ['issued', 'paid']) && ! $invoice->issued_at
                ? now()
                : $invoice->issued_at,
        ]);

        Auditor::logChange("Invoice status changed", $invoice, $old, $invoice->only('status', 'payment_method'));

        return redirect()->route('company.invoices.show', $invoice)
            ->with('success', __('Invoice updated.'));
    }

    /**
     * Void an invoice.
     */
    public function void(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->company_id === $this->company()->id, 403);
        abort_if(in_array($invoice->status, ['void']), 422);

        $invoice->update(['status' => 'void']);

        Auditor::log("Invoice {$invoice->invoice_number} voided", $invoice);

        return redirect()->route('company.invoices.show', $invoice)
            ->with('success', __('Invoice voided.'));
    }

    // ── Static helper used also from AppointmentController ───────────────────

    public static function buildInvoiceFromAppointment(Appointment $appointment, \App\Models\Company $company): Invoice
    {
        $actor = Auditor::actor();

        $invoice = Invoice::create([
            'invoice_number'  => Invoice::generateNumber($company->id),
            'company_id'      => $company->id,
            'branch_id'       => $appointment->branch_id,
            'booking_group_id'=> $appointment->booking_group_id,
            'appointment_id'  => $appointment->id,
            'customer_name'   => $appointment->displayName(),
            'customer_phone'  => $appointment->customer_phone ?? $appointment->customer?->phone,
            'currency'        => $appointment->service?->currency ?? config('booksy.default_currency', 'SYP'),
            'status'          => 'issued',
            'issued_at'       => now(),
            'created_by_type' => $actor['type'],
            'created_by_id'   => $actor['id'],
            'created_by_name' => $actor['name'],
        ]);

        $sort = 0;

        // Build items from appointment_services if available, else from main service
        $apptServices = $appointment->appointmentServices()->with(['service', 'employee'])->get();

        if ($apptServices->isNotEmpty()) {
            foreach ($apptServices as $as) {
                $itemTotal = (float) $as->price;
                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'type'          => 'service',
                    'description'   => $as->service?->localizedName() ?? '—',
                    'employee_name' => $as->employee?->localizedName(),
                    'customer_name' => $appointment->displayName(),
                    'unit_price'    => $as->price,
                    'qty'           => 1,
                    'total'         => $itemTotal,
                    'sort_order'    => $sort++,
                ]);
            }
        } elseif ($appointment->service) {
            $service   = $appointment->service;
            $itemTotal = (float) $appointment->total_price;
            InvoiceItem::create([
                'invoice_id'    => $invoice->id,
                'type'          => 'service',
                'description'   => $service->localizedName(),
                'employee_name' => $appointment->employee?->localizedName(),
                'customer_name' => $appointment->displayName(),
                'unit_price'    => $itemTotal,
                'qty'           => 1,
                'total'         => $itemTotal,
                'sort_order'    => $sort++,
            ]);
        }

        // If there's a booking group, include other persons' appointments
        if ($appointment->booking_group_id) {
            $siblings = Appointment::where('booking_group_id', $appointment->booking_group_id)
                ->where('id', '!=', $appointment->id)
                ->with(['appointmentServices.service', 'appointmentServices.employee', 'service', 'employee'])
                ->get();

            foreach ($siblings as $sibling) {
                $siblingServices = $sibling->appointmentServices;

                if ($siblingServices->isNotEmpty()) {
                    foreach ($siblingServices as $as) {
                        InvoiceItem::create([
                            'invoice_id'    => $invoice->id,
                            'type'          => 'service',
                            'description'   => $as->service?->localizedName() ?? '—',
                            'employee_name' => $as->employee?->localizedName(),
                            'customer_name' => $sibling->displayName(),
                            'unit_price'    => $as->price,
                            'qty'           => 1,
                            'total'         => (float) $as->price,
                            'sort_order'    => $sort++,
                        ]);
                    }
                } elseif ($sibling->service) {
                    InvoiceItem::create([
                        'invoice_id'    => $invoice->id,
                        'type'          => 'service',
                        'description'   => $sibling->service->localizedName(),
                        'employee_name' => $sibling->employee?->localizedName(),
                        'customer_name' => $sibling->displayName(),
                        'unit_price'    => (float) $sibling->total_price,
                        'qty'           => 1,
                        'total'         => (float) $sibling->total_price,
                        'sort_order'    => $sort++,
                    ]);
                }
            }
        }

        $invoice->load('items');
        $invoice->recalculate();

        return $invoice;
    }
}
