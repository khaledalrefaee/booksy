<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $company   = Auth::guard('company')->user();
        $q         = trim((string) $request->input('q', ''));
        $branchIds = $company->branches()->pluck('id');

        $results = [
            'customers'    => collect(),
            'appointments' => collect(),
            'invoices'     => collect(),
            'employees'    => collect(),
            'services'     => collect(),
            'branches'     => collect(),
            'inventory'    => collect(),
        ];

        if ($q !== '') {
            $results['customers'] = Customer::query()
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))
                ->where(fn ($w) => $w->whereHas('appointments', fn ($a) => $a->whereIn('branch_id', $branchIds))
                    ->orWhereDoesntHave('appointments'))
                ->limit(10)->get();

            $results['appointments'] = Appointment::query()
                ->where('company_id', $company->id)
                ->where(fn ($w) => $w->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%"))
                ->with(['branch', 'service'])
                ->latest('start_time')->limit(10)->get();

            $results['invoices'] = Invoice::query()
                ->where('company_id', $company->id)
                ->where(fn ($w) => $w->where('invoice_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%"))
                ->latest()->limit(10)->get();

            $results['employees'] = Employee::query()
                ->where('company_id', $company->id)
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))
                ->limit(10)->get();

            $results['services'] = Service::query()
                ->whereIn('branch_id', $branchIds)
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%"))
                ->with('branch')
                ->limit(10)->get();

            $results['branches'] = Branch::query()
                ->where('company_id', $company->id)
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%"))
                ->limit(10)->get();

            $results['inventory'] = Product::query()
                ->whereIn('branch_id', $branchIds)
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%"))
                ->limit(10)->get();
        }

        $totalCount = collect($results)->sum(fn ($c) => $c->count());

        return view('company.search.index', compact('q', 'results', 'totalCount'));
    }
}
