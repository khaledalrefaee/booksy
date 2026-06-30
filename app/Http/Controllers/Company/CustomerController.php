<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchPayment;
use App\Models\Customer;
use App\Models\CustomerBranchNote;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                ->orWhereDoesntHave('appointments')
            )
            ->withCount(['appointments as total_visits' => $branchScope])
            ->withMax(['appointments as last_visit' => $branchScope], 'start_time')
            ->withSum(['appointments as total_spent' => fn($q) => $q->whereIn('branch_id', $branchIds)->where('status', 'completed')], 'total_price');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('name', 'like', '%' . $s . '%')->orWhere('phone', 'like', '%' . $s . '%'));
        }

        if ($request->filled('branch_id')) {
            $bid = (int) $request->input('branch_id');
            $query->whereHas('appointments', fn($q) => $q->where('branch_id', $bid));
        }

        if ($request->filled('tag')) {
            $query->where('tag', $request->input('tag'));
        }

        if ($request->input('banned') === '1') {
            $query->where('is_banned', true);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->input('birthday_month') === '1') {
            $query->whereMonth('date_of_birth', now()->month);
        }

        if ($request->input('inactive') === '1') {
            $query->having('last_visit', '<', now()->subDays(config('booksy.miss_you_days', 30)));
        }

        if ($request->input('has_debt') === '1') {
            $query->whereHas('treatmentPlans', fn($q) => $q->where('status', 'active'));
        }

        $totalCustomers = (clone $query)->count();

        $newThisMonth = Customer::query()
            ->whereHas('appointments', fn($q) => $q->whereIn('branch_id', $branchIds)->where('start_time', '>=', now()->startOfMonth()))
            ->whereDoesntHave('appointments', fn($q) => $q->whereIn('branch_id', $branchIds)->where('start_time', '<', now()->startOfMonth()))
            ->count();

        $customers = $query->with('linkedBranches')->orderByDesc('total_visits')->paginate(10)->withQueryString();

        return view('company.customers.index', compact(
            'customers', 'branches', 'totalCustomers', 'newThisMonth'
        ));
    }

    private function buildPhone(Request $request): string
    {
        $dialCode    = $request->input('dial_code', config('booksy.default_dial_code', '+963'));
        $phoneNumber = preg_replace('/[^0-9]/', '', $request->input('phone_number', ''));

        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        $dialCodes = config('booksy.dial_codes', []);
        if (isset($dialCodes[$dialCode])) {
            $min = $dialCodes[$dialCode]['digits_min'];
            $max = $dialCodes[$dialCode]['digits_max'];
            $len = strlen($phoneNumber);
            if ($len < $min || $len > $max) {
                $country = $dialCodes[$dialCode]['name_ar'] ?? $dialCodes[$dialCode]['name_en'];
                $msg = ($min === $max)
                    ? __('Phone number for :country must be exactly :n digits.', ['country' => $country, 'n' => $min])
                    : __('Phone number for :country must be between :min and :max digits.', ['country' => $country, 'min' => $min, 'max' => $max]);
                throw \Illuminate\Validation\ValidationException::withMessages(['phone_number' => $msg]);
            }
        }

        return ltrim($dialCode, '+') . $phoneNumber;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'dial_code'     => ['required', 'string', 'max:5'],
            'phone_number'  => ['required', 'string', 'max:15'],
            'age'           => ['nullable', 'integer', 'min:1', 'max:120'],
            'notes'         => ['nullable', 'string', 'max:1000'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'source'        => ['nullable', 'in:' . implode(',', array_keys(Customer::SOURCES))],
            'branch_ids'    => ['nullable', 'array'],
            'branch_ids.*'  => ['exists:branches,id'],
        ]);

        $data['phone'] = $this->buildPhone($request);

        $customer = Customer::where('phone', $data['phone'])->first();

        if ($customer) {
            $customer->update(array_filter([
                'name'          => $data['name'],
                'age'           => $data['age'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'source'        => $data['source'] ?? null,
            ]));
            if (!empty($data['branch_ids'])) {
                $customer->linkedBranches()->syncWithoutDetaching($data['branch_ids']);
            }

            return redirect()->route('company.customers.index')
                ->with('success', __('Customer already exists — info updated.'));
        }

        $customer = Customer::create([
            'name'          => $data['name'],
            'phone'         => $data['phone'],
            'age'           => $data['age'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'source'        => $data['source'] ?? null,
        ]);

        if (!empty($data['branch_ids'])) {
            $customer->linkedBranches()->sync($data['branch_ids']);
        }

        Auditor::log("Added customer: {$data['name']} ({$data['phone']})");

        return redirect()->route('company.customers.index')
            ->with('success', __('Customer added successfully.'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'dial_code'     => ['required', 'string', 'max:5'],
            'phone_number'  => ['required', 'string', 'max:15'],
            'age'           => ['nullable', 'integer', 'min:1', 'max:120'],
            'notes'         => ['nullable', 'string', 'max:1000'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'source'        => ['nullable', 'in:' . implode(',', array_keys(Customer::SOURCES))],
            'branch_ids'    => ['nullable', 'array'],
            'branch_ids.*'  => ['exists:branches,id'],
        ]);

        $old = $customer->only(['name', 'phone', 'age', 'notes']);

        $customer->update([
            'name'          => $data['name'],
            'phone'         => $this->buildPhone($request),
            'age'           => $data['age'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? $customer->date_of_birth,
            'source'        => $data['source'] ?? $customer->source,
        ]);

        if (isset($data['branch_ids'])) {
            $customer->linkedBranches()->sync($data['branch_ids']);
        }

        Auditor::logChange("Updated customer #{$customer->id}", $customer, $old, $customer->only(['name', 'phone', 'age', 'notes']));

        return back()->with('success', __('Customer updated successfully.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Auditor::log("Deleted customer: {$customer->name} ({$customer->phone})", $customer);

        $customer->delete();

        return redirect()->route('company.customers.index')
            ->with('success', __('Customer deleted successfully.'));
    }

    public function updateTag(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'tag' => ['nullable', 'in:' . implode(',', array_keys(Customer::TAGS))],
        ]);

        $customer->update(['tag' => $data['tag']]);

        $label = $data['tag'] ? __(Customer::TAGS[$data['tag']]['label_key']) : __('None');
        Auditor::log("Updated customer tag: {$customer->name} → {$label}", $customer);

        return back()->with('success', __('Customer tag updated.'));
    }

    public function toggleBan(Request $request, Customer $customer): RedirectResponse
    {
        if ($customer->is_banned) {
            $customer->update([
                'is_banned'  => false,
                'ban_reason' => null,
                'banned_at'  => null,
            ]);

            Auditor::log("Unbanned customer: {$customer->name}", $customer);

            return back()->with('success', __('Customer unbanned.'));
        }

        $data = $request->validate([
            'ban_reason' => ['required', 'string', 'max:255'],
        ]);

        $customer->update([
            'is_banned'  => true,
            'ban_reason' => $data['ban_reason'],
            'banned_at'  => now(),
        ]);

        Auditor::log("Banned customer: {$customer->name} — {$data['ban_reason']}", $customer);

        return back()->with('success', __('Customer banned.'));
    }

    public function updateBranchNote(Request $request, Customer $customer, Branch $branch): RedirectResponse
    {
        $this->company(); // auth check
        abort_unless($branch->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($data['notes'])) {
            CustomerBranchNote::where('customer_id', $customer->id)
                ->where('branch_id', $branch->id)
                ->delete();
        } else {
            CustomerBranchNote::updateOrCreate(
                ['customer_id' => $customer->id, 'branch_id' => $branch->id],
                ['notes' => $data['notes']]
            );
        }

        return back()->with('success', __('Branch note updated.'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $company   = $this->company();
        $branchIds = $company->branches()->pluck('id');

        $branchScope = fn($q) => $q->whereIn('branch_id', $branchIds);

        $customers = Customer::query()
            ->where(fn($q) => $q
                ->whereHas('appointments', $branchScope)
                ->orWhereDoesntHave('appointments')
            )
            ->withCount(['appointments as total_visits' => $branchScope])
            ->withMax(['appointments as last_visit' => $branchScope], 'start_time')
            ->withSum(['appointments as total_spent' => fn($q) => $q->whereIn('branch_id', $branchIds)->where('status', 'completed')], 'total_price')
            ->orderByDesc('total_visits')
            ->get();

        $filename = 'customers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($customers) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [__('Name'), __('Phone'), __('Age'), __('Visits'), __('Total Spent'), __('Last Visit'), __('Notes')]);

            foreach ($customers as $c) {
                fputcsv($out, [
                    $c->name,
                    $c->phone,
                    $c->age ?? '',
                    $c->total_visits ?? 0,
                    $c->total_spent ? number_format((float)$c->total_spent, 0) : '0',
                    $c->last_visit ? \Carbon\Carbon::parse($c->last_visit)->format('Y-m-d') : '',
                    $c->notes ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

        $appointments = $customer->appointments()
            ->whereIn('branch_id', $branchIds)
            ->with(['service', 'employee', 'branch'])
            ->orderByDesc('start_time')
            ->paginate(10);

        $allAppointmentIds = $customer->appointments()
            ->whereIn('branch_id', $branchIds)
            ->pluck('id');

        $payments = BranchPayment::whereIn('appointment_id', $allAppointmentIds)
            ->orderByDesc('paid_at')
            ->paginate(10, ['*'], 'pay_page');

        $totalVisits = $customer->appointments()->whereIn('branch_id', $branchIds)->count();
        $lastVisit = $customer->appointments()->whereIn('branch_id', $branchIds)->max('start_time');
        $memberSince = $customer->appointments()->whereIn('branch_id', $branchIds)->min('start_time') ?? $customer->created_at;
        if (is_string($lastVisit)) $lastVisit = \Carbon\Carbon::parse($lastVisit);
        if (is_string($memberSince)) $memberSince = \Carbon\Carbon::parse($memberSince);

        $totalSpent = BranchPayment::whereIn('appointment_id', $allAppointmentIds)
            ->select('currency')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->map(fn($r) => [
                'amount'   => round((float) $r->total, 2),
                'currency' => $r->currency,
                'symbol'   => config("booksy.currencies.{$r->currency}.symbol", $r->currency),
            ]);

        $topServices = $customer->appointments()
            ->whereIn('branch_id', $branchIds)
            ->whereNotNull('service_id')
            ->select('service_id')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('service_id')
            ->orderByDesc('cnt')
            ->limit(3)
            ->with('service')
            ->get()
            ->map(fn($r) => ['service' => $r->service, 'count' => $r->cnt]);

        $branches = $company->branches()->orderBy('sort_order')->get();
        $customer->load(['branchNotes', 'linkedBranches', 'treatmentPlans.sessions', 'treatmentPlans.branch']);

        $loyaltyLogs = $customer->loyaltyPointLogs()->orderByDesc('created_at')->limit(20)->get();
        $communications = $customer->communications()->with('branch')->orderByDesc('created_at')->paginate(10, ['*'], 'comm_page');

        return view('company.customers.show', compact(
            'customer', 'appointments', 'payments', 'totalVisits',
            'lastVisit', 'memberSince', 'totalSpent', 'topServices', 'branches',
            'loyaltyLogs', 'communications'
        ));
    }
}
