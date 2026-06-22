<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BranchPayment;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    public function index(Request $request): View
    {
        $company   = $this->company();
        $branchIds = $company->branches()->pluck('id');
        $branches  = $company->branches()->orderBy('sort_order')->get();

        $branchScope = fn($q) => $q->whereIn('branch_id', $branchIds);

        $query = Customer::query()
            ->where(fn($q) => $q
                ->whereHas('appointments', $branchScope)
                ->orWhereDoesntHave('appointments') // include imported customers without appointments
            )
            ->withCount(['appointments as total_visits' => $branchScope])
            ->withMax(['appointments as last_visit' => $branchScope], 'start_time')
            ->withSum(['appointments as total_spent' => fn($q) => $q->whereIn('branch_id', $branchIds)->where('status', 'completed')], 'total_price');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
        }

        if ($request->filled('branch_id')) {
            $bid = $request->input('branch_id');
            $query->whereHas('appointments', fn($q) => $q->where('branch_id', $bid));
        }

        $totalCustomers = (clone $query)->count();

        $newThisMonth = Customer::query()
            ->whereHas('appointments', fn($q) => $q->whereIn('branch_id', $branchIds)->where('start_time', '>=', now()->startOfMonth()))
            ->whereDoesntHave('appointments', fn($q) => $q->whereIn('branch_id', $branchIds)->where('start_time', '<', now()->startOfMonth()))
            ->count();

        $customers = $query->orderByDesc('total_visits')->paginate(10)->withQueryString();

        return view('company.customers.index', compact(
            'customers', 'branches', 'totalCustomers', 'newThisMonth'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'age'   => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $customer = Customer::where('phone', $data['phone'])->first();

        if ($customer) {
            $customer->update(array_filter([
                'name' => $data['name'],
                'age'  => $data['age'] ?? null,
            ]));

            return redirect()->route('company.customers.index')
                ->with('success', __('Customer already exists — info updated.'));
        }

        Customer::create($data);

        return redirect()->route('company.customers.index')
            ->with('success', __('Customer added successfully.'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if ($ext === 'csv') {
            $rows = $this->parseCsv($file->getRealPath());
        } else {
            $rows = $this->parseExcel($file->getRealPath());
        }

        if (empty($rows)) {
            return back()->with('error', __('No data found in the file.'));
        }

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name  = trim($row['name'] ?? $row[0] ?? '');
                $phone = trim($row['phone'] ?? $row[1] ?? '');
                $age   = isset($row['age']) ? (int) $row['age'] : (isset($row[2]) ? (int) $row[2] : null);

                if (!$name || !$phone) {
                    $skipped++;
                    continue;
                }

                $existing = Customer::where('phone', $phone)->first();
                if ($existing) {
                    $existing->update(array_filter(['name' => $name, 'age' => $age]));
                    $updated++;
                } else {
                    Customer::create([
                        'name'  => $name,
                        'phone' => $phone,
                        'age'   => $age ?: null,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Import failed: :msg', ['msg' => $e->getMessage()]));
        }

        $msg = __(':imported new, :updated updated, :skipped skipped.', [
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
        ]);

        return redirect()->route('company.customers.index')
            ->with('success', __('Import completed.') . ' ' . $msg);
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        $header = fgetcsv($handle);
        if (!$header) { fclose($handle); return []; }

        $header = array_map(fn($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', $h))), $header);
        $hasNamedCols = in_array('name', $header) || in_array('الاسم', $header);

        if ($hasNamedCols) {
            $nameIdx  = $this->findCol($header, ['name', 'الاسم', 'customer_name', 'اسم']);
            $phoneIdx = $this->findCol($header, ['phone', 'الهاتف', 'رقم', 'mobile', 'الجوال', 'رقم الهاتف']);
            $ageIdx   = $this->findCol($header, ['age', 'العمر', 'عمر']);

            while (($line = fgetcsv($handle)) !== false) {
                $rows[] = [
                    'name'  => $line[$nameIdx] ?? '',
                    'phone' => $line[$phoneIdx] ?? '',
                    'age'   => $ageIdx !== null ? ($line[$ageIdx] ?? null) : null,
                ];
            }
        } else {
            $rows[] = ['name' => $header[0] ?? '', 'phone' => $header[1] ?? '', 'age' => $header[2] ?? null];
            while (($line = fgetcsv($handle)) !== false) {
                $rows[] = ['name' => $line[0] ?? '', 'phone' => $line[1] ?? '', 'age' => $line[2] ?? null];
            }
        }

        fclose($handle);
        return $rows;
    }

    private function parseExcel(string $path): array
    {
        $rows = [];
        $data = Excel::toArray(null, $path);
        if (empty($data) || empty($data[0])) return [];

        $sheet  = $data[0];
        $header = array_map(fn($h) => strtolower(trim((string)$h)), $sheet[0] ?? []);
        $hasNamedCols = in_array('name', $header) || in_array('الاسم', $header);

        if ($hasNamedCols) {
            $nameIdx  = $this->findCol($header, ['name', 'الاسم', 'customer_name', 'اسم']);
            $phoneIdx = $this->findCol($header, ['phone', 'الهاتف', 'رقم', 'mobile', 'الجوال', 'رقم الهاتف']);
            $ageIdx   = $this->findCol($header, ['age', 'العمر', 'عمر']);

            for ($i = 1; $i < count($sheet); $i++) {
                $line = $sheet[$i];
                $rows[] = [
                    'name'  => $line[$nameIdx] ?? '',
                    'phone' => $line[$phoneIdx] ?? '',
                    'age'   => $ageIdx !== null ? ($line[$ageIdx] ?? null) : null,
                ];
            }
        } else {
            foreach ($sheet as $line) {
                $rows[] = ['name' => $line[0] ?? '', 'phone' => $line[1] ?? '', 'age' => $line[2] ?? null];
            }
        }

        return $rows;
    }

    private function findCol(array $header, array $candidates): ?int
    {
        foreach ($candidates as $name) {
            $idx = array_search($name, $header);
            if ($idx !== false) return $idx;
        }
        return null;
    }

    public function show(Customer $customer): View
    {
        $company   = $this->company();
        $branchIds = $company->branches()->pluck('id');

        abort_unless(
            $customer->appointments()->whereIn('branch_id', $branchIds)->exists()
            || true, // Allow viewing any customer (they may have been imported without appointments)
            404
        );

        $appointments = $customer->appointments()
            ->whereIn('branch_id', $branchIds)
            ->with(['service', 'employee', 'branch'])
            ->orderByDesc('start_time')
            ->get();

        $payments = BranchPayment::whereIn('appointment_id', $appointments->pluck('id'))
            ->orderByDesc('paid_at')
            ->get();

        $totalVisits = $appointments->count();
        $lastVisit = $appointments->first()?->start_time;
        $memberSince = $appointments->last()?->start_time ?? $customer->created_at;

        $totalSpent = $payments->groupBy('currency')->map(fn($rows) => [
            'amount'   => round($rows->sum('amount'), 2),
            'currency' => $rows->first()->currency,
            'symbol'   => config("booksy.currencies.{$rows->first()->currency}.symbol", $rows->first()->currency),
        ]);

        $topServices = $appointments->where('service_id', '!=', null)
            ->groupBy('service_id')
            ->map(fn($group) => [
                'service' => $group->first()->service,
                'count'   => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(3)
            ->values();

        return view('company.customers.show', compact(
            'customer', 'appointments', 'payments', 'totalVisits',
            'lastVisit', 'memberSince', 'totalSpent', 'topServices'
        ));
    }
}
