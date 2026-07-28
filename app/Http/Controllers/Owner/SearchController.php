<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $results = [
            'companies'    => collect(),
            'branches'     => collect(),
            'employees'    => collect(),
            'services'     => collect(),
            'appointments' => collect(),
        ];

        if ($q !== '') {
            $results['companies'] = Company::query()
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%"))
                ->limit(10)->get();

            $results['branches'] = Branch::query()
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))
                ->with('company')
                ->limit(10)->get();

            $results['employees'] = Employee::query()
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))
                ->with('branch')
                ->limit(10)->get();

            $results['services'] = Service::query()
                ->where(fn ($w) => $w->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%"))
                ->with('branch')
                ->limit(10)->get();

            $results['appointments'] = Appointment::query()
                ->where(fn ($w) => $w->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%"))
                ->with(['company', 'branch'])
                ->latest('start_time')->limit(10)->get();
        }

        $totalCount = collect($results)->sum(fn ($c) => $c->count());

        return view('owner.search.index', compact('q', 'results', 'totalCount'));
    }
}
