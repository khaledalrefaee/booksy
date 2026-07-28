<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cross-tenant read-only invoices ledger (GMV view). */
class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $filterCompanyId = $request->input('company_id', '');
        $filterStatus    = $request->input('status', '');
        $filterFrom      = $request->input('from', '');
        $filterTo        = $request->input('to', '');

        $query = Invoice::query()->with(['company', 'branch'])->latest('issued_at')->latest('id');

        if ($filterCompanyId !== '') {
            $query->where('company_id', (int) $filterCompanyId);
        }

        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        if ($filterFrom !== '') {
            $query->whereDate('issued_at', '>=', $filterFrom);
        }

        if ($filterTo !== '') {
            $query->whereDate('issued_at', '<=', $filterTo);
        }

        // GMV per currency for the current filter set.
        $totals = (clone $query)->reorder()
            ->selectRaw('currency, SUM(total) as gmv, SUM(amount_paid) as paid, COUNT(*) as cnt')
            ->groupBy('currency')
            ->get();

        $invoices  = $query->paginate(20)->withQueryString();
        $companies = Company::query()->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar)")->get(['id', 'name_en', 'name_ar']);
        $statuses  = Invoice::query()->select('status')->distinct()->orderBy('status')->pluck('status');

        return view('owner.invoices.index', compact(
            'invoices', 'totals', 'companies', 'statuses',
            'filterCompanyId', 'filterStatus', 'filterFrom', 'filterTo',
        ));
    }
}
