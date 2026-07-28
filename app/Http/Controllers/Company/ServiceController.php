<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    private function authoriseService(Service $service): void
    {
        abort_unless($service->branch->company_id === $this->company()->id, 403);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Index
    // ─────────────────────────────────────────────────────────────────────
    public function index(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $services = $branch->services()
            ->with(['serviceCategory', 'employees:id', 'resources:id', 'packageItems:id,name_en,name_ar', 'parentServices:id'])
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        $serviceCategories = $this->company()->serviceCategories()
            ->orderBy('sort_order')->orderBy('name_en')->get();

        // Sibling branches for the "copy to branches" flow.
        $siblingBranches = $this->company()->branches()
            ->where('id', '!=', $branch->id)
            ->orderBy('name_en')->get(['id', 'name_en', 'name_ar']);

        $branchEmployees = $branch->employees()
            ->orderBy('name_en')->get(['id', 'name_en', 'name_ar', 'is_active']);

        $branchResources = $branch->resources()->get();

        // Compact JSON snapshot that the client-side workbench uses as its source
        // of truth for instant, optimistic, reload-free updates.
        $servicesData = $services->map(fn ($s) => $this->serviceArray($s))->values();

        return view('company.services.index', compact(
            'branch', 'services', 'serviceCategories',
            'siblingBranches', 'branchEmployees', 'branchResources', 'servicesData'
        ));
    }

    /** Legacy standalone create page (kept for deep links / no-JS fallback). */
    public function create(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $serviceCategories = $this->company()->serviceCategories()->orderBy('sort_order')->get();
        $branchResources   = $branch->resources()->get();

        return view('company.services.create', compact('branch', 'serviceCategories', 'branchResources'));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Store / Update
    // ─────────────────────────────────────────────────────────────────────
    public function store(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $this->authoriseBranch($branch);

        $request->validate($this->rules($branch));
        $data = $this->prepareData($request);
        $data['sort_order'] = (int) $branch->services()->max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service = $branch->services()->create($data);
        $this->syncRelations($service, $request, $branch);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $service->id, 'service' => $this->serviceArray($service->fresh(['serviceCategory', 'employees:id', 'resources:id', 'packageItems', 'parentServices:id']))]);
        }

        return redirect()
            ->route('company.branches.services.index', $branch)
            ->with('success', __('Service created successfully.'));
    }

    public function edit(Service $service): View
    {
        $service->load('branch');
        $this->authoriseService($service);

        $serviceCategories  = $this->company()->serviceCategories()->orderBy('sort_order')->get();
        $branchResources    = $service->branch->resources()->get();
        $linkedResourceIds  = $service->resources()->pluck('resources.id')->all();

        $inheritedResources = $service->service_category_id
            ? $service->branch->resources()
                ->whereHas('serviceCategories', fn ($q) => $q->where('service_categories.id', $service->service_category_id))
                ->get()
            : collect();

        return view('company.services.edit', compact('service', 'serviceCategories', 'branchResources', 'linkedResourceIds', 'inheritedResources'));
    }

    public function update(Request $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);
        $branch = $service->branch;

        $request->validate($this->rules($branch, $service));
        $data = $this->prepareData($request);

        if ($request->hasFile('image')) {
            if ($service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }
            $data['image_path'] = $request->file('image')->store('services', 'public');
        } elseif ($request->boolean('remove_image') && $service->image_path) {
            Storage::disk('public')->delete($service->image_path);
            $data['image_path'] = null;
        }

        $service->update($data);
        $this->syncRelations($service, $request, $branch);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'service' => $this->serviceArray($service->fresh(['serviceCategory', 'employees:id', 'resources:id', 'packageItems', 'parentServices:id']))]);
        }

        return redirect()
            ->route('company.branches.services.index', $branch)
            ->with('success', __('Service updated successfully.'));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Quick toggles (active / online) — optimistic UI targets
    // ─────────────────────────────────────────────────────────────────────
    public function toggleActive(Request $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $field = in_array($request->input('field'), ['is_bookable_online'], true)
            ? 'is_bookable_online'
            : 'is_active';

        $service->update([$field => ! $service->{$field}]);

        if ($request->expectsJson()) {
            return response()->json(['is_active' => $service->is_active, 'is_bookable_online' => $service->is_bookable_online, 'field' => $field]);
        }

        return redirect()
            ->route('company.branches.services.index', $service->branch)
            ->with('success', __('Service status updated.'));
    }

    public function destroy(Request $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $branch = $service->branch;
        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }
        $service->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('company.branches.services.index', $branch)
            ->with('success', __('Service deleted successfully.'));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Duplicate (same branch)
    // ─────────────────────────────────────────────────────────────────────
    public function duplicate(Service $service): JsonResponse
    {
        $service->load(['branch', 'employees:id', 'resources:id', 'packageItems']);
        $this->authoriseService($service);
        $branch = $service->branch;

        $clone = $service->replicate(['image_path']);
        $clone->name_en   = $service->name_en ? $service->name_en . ' (Copy)' : null;
        $clone->name_ar   = $service->name_ar ? $service->name_ar . ' (نسخة)' : null;
        $clone->sort_order = (int) $branch->services()->max('sort_order') + 1;
        // Copy the image file so deleting one doesn't orphan the other.
        if ($service->image_path && Storage::disk('public')->exists($service->image_path)) {
            $ext = pathinfo($service->image_path, PATHINFO_EXTENSION);
            $new = 'services/' . Str::uuid() . ($ext ? ".{$ext}" : '');
            Storage::disk('public')->copy($service->image_path, $new);
            $clone->image_path = $new;
        }
        $clone->save();

        $clone->employees()->sync($service->employees->pluck('id'));
        $clone->resources()->sync($service->resources->pluck('id'));
        foreach ($service->packageItems as $item) {
            $clone->packageItems()->attach($item->id, [
                'is_optional' => $item->pivot->is_optional,
                'quantity'    => $item->pivot->quantity,
                'sort_order'  => $item->pivot->sort_order,
            ]);
        }

        return response()->json(['success' => true, 'service' => $this->serviceArray($clone->fresh(['serviceCategory', 'employees:id', 'resources:id', 'packageItems', 'parentServices:id']))]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Bulk actions
    // ─────────────────────────────────────────────────────────────────────
    public function bulk(Request $request, Branch $branch): JsonResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'action'  => ['required', 'string', 'in:activate,deactivate,show_online,hide_online,delete,duplicate,move_category,price,duration'],
            'ids'     => ['required', 'array', 'min:1'],
            'ids.*'   => ['integer'],
            'category_id' => ['nullable', Rule::exists('service_categories', 'id')->where('company_id', $this->company()->id)],
            'price_mode'  => ['nullable', 'in:increase_percent,decrease_percent,increase_amount,decrease_amount,set'],
            'price_value' => ['nullable', 'numeric', 'min:0'],
            'duration_mode'  => ['nullable', 'in:set,increase,decrease'],
            'duration_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $services = $branch->services()->whereIn('id', $data['ids'])->get();
        $affected = 0;
        $created  = [];

        foreach ($services as $service) {
            switch ($data['action']) {
                case 'activate':      $service->update(['is_active' => true]); break;
                case 'deactivate':    $service->update(['is_active' => false]); break;
                case 'show_online':   $service->update(['is_bookable_online' => true]); break;
                case 'hide_online':   $service->update(['is_bookable_online' => false]); break;
                case 'move_category': $service->update(['service_category_id' => $data['category_id'] ?? null]); break;
                case 'delete':
                    if ($service->image_path) Storage::disk('public')->delete($service->image_path);
                    $service->delete();
                    break;
                case 'duplicate':
                    $created[] = $this->duplicate($service)->getData(true)['service'];
                    break;
                case 'price':
                    $service->update($this->applyPrice($service, $data['price_mode'] ?? 'set', (float) ($data['price_value'] ?? 0)));
                    break;
                case 'duration':
                    $service->update(['duration_minutes' => $this->applyDuration($service, $data['duration_mode'] ?? 'set', (int) ($data['duration_value'] ?? 0))]);
                    break;
            }
            $affected++;
        }

        return response()->json(['success' => true, 'affected' => $affected, 'created' => $created]);
    }

    private function applyPrice(Service $service, string $mode, float $value): array
    {
        $calc = function ($base) use ($mode, $value) {
            if ($base === null) return null;
            $base = (float) $base;
            $out = match ($mode) {
                'increase_percent' => $base * (1 + $value / 100),
                'decrease_percent' => $base * (1 - $value / 100),
                'increase_amount'  => $base + $value,
                'decrease_amount'  => $base - $value,
                default            => $value, // set
            };
            return round(max(0, $out), 2);
        };

        $out = ['price' => $calc($service->price)];
        if ($service->price_type === 'range' && $service->price_to !== null) {
            $out['price_to'] = $calc($service->price_to);
        }
        return $out;
    }

    private function applyDuration(Service $service, string $mode, int $value): int
    {
        $out = match ($mode) {
            'increase' => $service->duration_minutes + $value,
            'decrease' => $service->duration_minutes - $value,
            default    => $value,
        };
        return max(1, min(1440, $out));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Copy services to other branches
    // ─────────────────────────────────────────────────────────────────────
    public function copyToBranches(Request $request, Branch $branch): JsonResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'service_ids'        => ['required', 'array', 'min:1'],
            'service_ids.*'      => ['integer'],
            'target_branch_ids'  => ['required', 'array', 'min:1'],
            'target_branch_ids.*'=> ['integer'],
            'strategy'           => ['required', 'in:skip,replace,duplicate'],
            'dry_run'            => ['nullable', 'boolean'],
        ]);

        $sources = $branch->services()
            ->whereIn('id', $data['service_ids'])
            ->with('packageItems')
            ->get();

        $targets = $this->company()->branches()
            ->whereIn('id', $data['target_branch_ids'])
            ->where('id', '!=', $branch->id)
            ->get();

        $dryRun  = (bool) ($data['dry_run'] ?? false);
        $summary = [];

        // The whole copy loop; run read-only for a preview, or inside one
        // transaction for the real thing (no manual rollback needed).
        $apply = function () use ($sources, $targets, $data, $dryRun, &$summary) {
            foreach ($targets as $target) {
                $created = $updated = $skipped = 0;

                foreach ($sources as $source) {
                    $existing = $target->services()
                        ->where('name_en', $source->name_en)
                        ->when($source->name_en === null, fn ($q) => $q->where('name_ar', $source->name_ar))
                        ->first();

                    if ($existing && $data['strategy'] === 'skip') {
                        $skipped++;
                        continue;
                    }

                    if ($dryRun) {
                        $existing ? $updated++ : $created++;
                        continue;
                    }

                    $attrs = $source->only([
                        'service_category_id', 'service_type', 'name_en', 'name_ar', 'description',
                        'price', 'price_type', 'price_to', 'currency', 'duration_minutes',
                        'is_active', 'is_bookable_online', 'badges', 'is_free', 'requires_approval',
                        'membership_validity_days', 'membership_sessions',
                        'discount_type', 'discount_value', 'discount_starts_at', 'discount_ends_at',
                    ]);

                    if ($existing && $data['strategy'] === 'replace') {
                        $existing->update($attrs);
                        $this->copyPackageItems($source, $existing, $target);
                        $updated++;
                    } else {
                        // duplicate strategy, or no existing row
                        if ($existing && $data['strategy'] === 'duplicate') {
                            $attrs['name_en'] = $source->name_en ? $source->name_en . ' (Copy)' : null;
                        }
                        $attrs['sort_order'] = (int) $target->services()->max('sort_order') + 1;
                        $new = $target->services()->create($attrs);
                        $this->copyPackageItems($source, $new, $target);
                        $created++;
                    }
                }

                $summary[] = [
                    'branch_id'   => $target->id,
                    'branch_name' => $target->localizedName(),
                    'created'     => $created,
                    'updated'     => $updated,
                    'skipped'     => $skipped,
                ];
            }
        };

        $dryRun ? $apply() : DB::transaction($apply);

        return response()->json([
            'success'     => true,
            'dry_run'     => $dryRun,
            'summary'     => $summary,
            'total_created' => collect($summary)->sum('created'),
            'total_updated' => collect($summary)->sum('updated'),
            'total_skipped' => collect($summary)->sum('skipped'),
        ]);
    }

    /** Re-link a copied package/membership's children by matching child names in the target branch. */
    private function copyPackageItems(Service $source, Service $target, Branch $targetBranch): void
    {
        if (! $source->bundlesServices()) {
            return;
        }
        $target->packageItems()->detach();
        foreach ($source->packageItems as $child) {
            $match = $targetBranch->services()
                ->where('name_en', $child->name_en)
                ->when($child->name_en === null, fn ($q) => $q->where('name_ar', $child->name_ar))
                ->first();
            if ($match && $match->id !== $target->id) {
                $target->packageItems()->syncWithoutDetaching([$match->id => [
                    'is_optional' => $child->pivot->is_optional,
                    'quantity'    => $child->pivot->quantity,
                    'sort_order'  => $child->pivot->sort_order,
                ]]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Drag-and-drop reorder
    // ─────────────────────────────────────────────────────────────────────
    public function reorder(Request $request, Branch $branch): JsonResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'items'                       => ['required', 'array'],
            'items.*.id'                  => ['required', 'integer'],
            'items.*.sort_order'          => ['required', 'integer'],
            'items.*.service_category_id' => ['nullable', 'integer'],
        ]);

        $validIds = $branch->services()->pluck('id')->flip();
        $validCats = $this->company()->serviceCategories()->pluck('id')->flip();

        DB::transaction(function () use ($data, $validIds, $validCats) {
            foreach ($data['items'] as $item) {
                if (! isset($validIds[$item['id']])) continue;
                $update = ['sort_order' => $item['sort_order']];
                if (array_key_exists('service_category_id', $item)) {
                    $cat = $item['service_category_id'];
                    $update['service_category_id'] = ($cat !== null && isset($validCats[$cat])) ? $cat : null;
                }
                Service::where('id', $item['id'])->update($update);
            }
        });

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Export / Import (CSV — opens natively in Excel; .xlsx accepted on import)
    // ─────────────────────────────────────────────────────────────────────
    public function exportCsv(Branch $branch): StreamedResponse
    {
        $this->authoriseBranch($branch);

        $services = $branch->services()->with('serviceCategory')->orderBy('sort_order')->get();
        $filename = 'services-' . Str::slug($branch->name_en ?: 'branch') . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($services) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel + Arabic
            fputcsv($out, ['type', 'category', 'name_en', 'name_ar', 'description', 'price_type', 'price', 'price_to', 'currency', 'duration_minutes', 'is_active', 'is_bookable_online', 'is_popular', 'is_recommended']);
            foreach ($services as $s) {
                fputcsv($out, [
                    $s->service_type,
                    $s->serviceCategory?->name_en,
                    $s->name_en,
                    $s->name_ar,
                    $s->description,
                    $s->price_type,
                    $s->price,
                    $s->price_to,
                    $s->currency,
                    $s->duration_minutes,
                    $s->is_active ? 1 : 0,
                    $s->is_bookable_online ? 1 : 0,
                    $s->is_popular ? 1 : 0,
                    $s->is_recommended ? 1 : 0,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importCsv(Request $request, Branch $branch): JsonResponse
    {
        $this->authoriseBranch($branch);

        $request->validate([
            'file'     => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:8192'],
            'strategy' => ['nullable', 'in:skip,replace,duplicate'],
        ]);
        $strategy = $request->input('strategy', 'skip');

        $rows = Excel::toArray(null, $request->file('file'));
        $sheet = $rows[0] ?? [];
        if (count($sheet) < 2) {
            return response()->json(['success' => false, 'message' => __('The file has no data rows.')], 422);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) str_replace("\xEF\xBB\xBF", '', (string) $h))), $sheet[0]);
        $col = fn (array $names) => collect($names)->map(fn ($n) => array_search($n, $header))->first(fn ($i) => $i !== false);

        $idx = [
            'type'     => $col(['type', 'service_type', 'النوع']),
            'category' => $col(['category', 'الفئة', 'التصنيف']),
            'name_en'  => $col(['name_en', 'name', 'الاسم']),
            'name_ar'  => $col(['name_ar', 'الاسم بالعربية']),
            'desc'     => $col(['description', 'الوصف']),
            'ptype'    => $col(['price_type']),
            'price'    => $col(['price', 'السعر']),
            'price_to' => $col(['price_to']),
            'currency' => $col(['currency', 'العملة']),
            'duration' => $col(['duration_minutes', 'duration', 'المدة']),
            'active'   => $col(['is_active', 'active']),
            'online'   => $col(['is_bookable_online', 'online']),
        ];

        if ($idx['name_en'] === null && $idx['name_ar'] === null) {
            return response()->json(['success' => false, 'message' => __('Could not find a name column.')], 422);
        }

        $categories = $this->company()->serviceCategories()->get();
        $validCurrencies = array_keys(config('booksy.currencies'));
        $created = $updated = $skipped = 0;

        DB::transaction(function () use ($sheet, $idx, $strategy, $branch, $categories, $validCurrencies, &$created, &$updated, &$skipped) {
            for ($i = 1; $i < count($sheet); $i++) {
                $line = $sheet[$i];
                $get = fn ($k) => $idx[$k] !== null ? trim((string) ($line[$idx[$k]] ?? '')) : '';

                $nameEn = $get('name_en');
                $nameAr = $get('name_ar');
                if ($nameEn === '' && $nameAr === '') continue;

                $existing = $branch->services()
                    ->where('name_en', $nameEn !== '' ? $nameEn : null)
                    ->when($nameEn === '', fn ($q) => $q->where('name_ar', $nameAr))
                    ->first();

                if ($existing && $strategy === 'skip') { $skipped++; continue; }

                $catName = $get('category');
                $cat = $catName !== '' ? $categories->first(fn ($c) => strcasecmp((string) $c->name_en, $catName) === 0 || strcasecmp((string) $c->name_ar, $catName) === 0) : null;

                $currency = strtoupper($get('currency'));
                $ptype = in_array($get('ptype'), Service::PRICE_TYPES, true) ? $get('ptype') : 'fixed';
                $type  = in_array($get('type'), Service::TYPES, true) ? $get('type') : 'standard';

                $attrs = [
                    'service_type'        => $type,
                    'service_category_id' => $cat?->id,
                    'name_en'             => $nameEn ?: null,
                    'name_ar'             => $nameAr ?: null,
                    'description'         => $get('desc') ?: null,
                    'price_type'          => $ptype,
                    'price'               => is_numeric($get('price')) ? (float) $get('price') : 0,
                    'price_to'            => $ptype === 'range' && is_numeric($get('price_to')) ? (float) $get('price_to') : null,
                    'currency'            => in_array($currency, $validCurrencies, true) ? $currency : config('booksy.default_currency'),
                    'duration_minutes'    => max(1, min(1440, (int) ($get('duration') ?: 30))),
                    'is_active'           => $idx['active'] === null ? true : in_array(strtolower($get('active')), ['1', 'yes', 'true', 'active'], true),
                    'is_bookable_online'  => $idx['online'] === null ? true : in_array(strtolower($get('online')), ['1', 'yes', 'true'], true),
                ];

                if ($existing && $strategy === 'replace') {
                    $existing->update($attrs);
                    $updated++;
                } else {
                    if ($existing && $strategy === 'duplicate' && $attrs['name_en']) {
                        $attrs['name_en'] .= ' (Copy)';
                    }
                    $attrs['sort_order'] = (int) $branch->services()->max('sort_order') + 1;
                    $branch->services()->create($attrs);
                    $created++;
                }
            }
        });

        return response()->json(['success' => true, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Shared helpers
    // ─────────────────────────────────────────────────────────────────────
    private function rules(Branch $branch, ?Service $ignore = null): array
    {
        return [
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'service_type'        => ['nullable', Rule::in(Service::TYPES)],
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'price'               => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'price_type'          => ['nullable', Rule::in(Service::PRICE_TYPES)],
            'price_to'            => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'gte:price', 'required_if:price_type,range'],
            'currency'            => ['required', 'string', 'in:' . implode(',', array_keys(config('booksy.currencies')))],
            'duration_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active'           => ['nullable', 'boolean'],
            'is_bookable_online'  => ['nullable', 'boolean'],
            'badges'              => ['nullable', 'array'],
            'badges.*'            => [Rule::in(Service::BADGES)],
            'is_free'             => ['nullable', 'boolean'],
            'requires_approval'   => ['nullable', 'boolean'],
            'membership_validity_days' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'membership_sessions'      => ['nullable', 'integer', 'min:0', 'max:100000'],
            'addon_parent_ids'    => ['nullable', 'array'],
            'addon_parent_ids.*'  => ['integer', Rule::exists('services', 'id')->where('branch_id', $branch->id)],
            'discount_type'       => ['nullable', 'in:percent,fixed'],
            'discount_value'      => ['nullable', 'numeric', 'min:0'],
            'discount_starts_at'  => ['nullable', 'date'],
            'discount_ends_at'    => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'resource_ids'        => ['nullable', 'array'],
            'resource_ids.*'      => ['integer'],
            'employee_ids'        => ['nullable', 'array'],
            'employee_ids.*'      => ['integer'],
            'package_items'                => ['nullable', 'array'],
            'package_items.*.service_id'   => ['required', 'integer', Rule::exists('services', 'id')->where('branch_id', $branch->id)],
            'package_items.*.is_optional'  => ['nullable', 'boolean'],
            'package_items.*.quantity'     => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    private function prepareData(Request $request): array
    {
        $type = in_array($request->input('service_type'), Service::TYPES, true) ? $request->input('service_type') : 'standard';
        $priceType = in_array($request->input('price_type'), Service::PRICE_TYPES, true) ? $request->input('price_type') : 'fixed';
        $freeConsult = $type === 'consultation' && $request->boolean('is_free');

        return [
            'service_category_id' => $request->input('service_category_id') ?: null,
            'service_type'        => $type,
            'name_en'             => $request->input('name_en'),
            'name_ar'             => $request->input('name_ar'),
            'description'         => $request->input('description'),
            'price'               => $freeConsult ? 0 : $request->input('price'),
            'price_type'          => $freeConsult ? 'fixed' : $priceType,
            'price_to'            => (! $freeConsult && $priceType === 'range') ? $request->input('price_to') : null,
            'currency'            => $request->input('currency'),
            'duration_minutes'    => $request->input('duration_minutes'),
            'is_active'           => $request->boolean('is_active'),
            'is_bookable_online'  => $request->has('is_bookable_online') ? $request->boolean('is_bookable_online') : true,
            // Badges: keep only known values; an add-on/consultation can still carry them.
            'badges'              => collect((array) $request->input('badges', []))
                                        ->filter(fn ($b) => in_array($b, Service::BADGES, true))
                                        ->values()->all() ?: null,
            // Consultation semantics (ignored for other types)
            'is_free'             => $type === 'consultation' ? $request->boolean('is_free') : false,
            'requires_approval'   => $type === 'consultation' ? $request->boolean('requires_approval') : false,
            'membership_validity_days' => $type === 'membership' ? ($request->input('membership_validity_days') ?: null) : null,
            'membership_sessions'      => $type === 'membership' ? ($request->input('membership_sessions') ?: null) : null,
            'discount_type'       => $request->filled('discount_value') ? $request->input('discount_type') : null,
            'discount_value'      => $request->filled('discount_value') ? $request->input('discount_value') : null,
            'discount_starts_at'  => $request->filled('discount_starts_at') ? $request->input('discount_starts_at') : null,
            'discount_ends_at'    => $request->filled('discount_ends_at') ? $request->input('discount_ends_at') : null,
        ];
    }

    /** Sync employees, resources and package items, only when the field is present. */
    private function syncRelations(Service $service, Request $request, Branch $branch): void
    {
        if ($request->has('resource_ids')) {
            $service->resources()->sync(
                $branch->resources()->whereIn('id', (array) $request->input('resource_ids', []))->pluck('id')
            );
        }
        if ($request->has('employee_ids')) {
            $service->employees()->sync(
                $branch->employees()->whereIn('id', (array) $request->input('employee_ids', []))->pluck('id')
            );
        }
        // Packages AND memberships bundle child services through the same pivot.
        if ($request->has('package_items') && $service->bundlesServices()) {
            $sync = [];
            $order = 0;
            foreach ((array) $request->input('package_items', []) as $item) {
                $childId = (int) ($item['service_id'] ?? 0);
                if ($childId === $service->id || $childId <= 0) continue;
                $sync[$childId] = [
                    'is_optional' => filter_var($item['is_optional'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'quantity'    => max(1, (int) ($item['quantity'] ?? 1)),
                    'sort_order'  => $order++,
                ];
            }
            $service->packageItems()->sync($sync);
        } elseif (! $service->bundlesServices() && $request->has('package_items')) {
            $service->packageItems()->detach();
        }

        // Add-ons attach to parent services; anything else clears the link.
        if ($request->has('addon_parent_ids') && $service->isAddon()) {
            $parents = $branch->services()
                ->whereIn('id', (array) $request->input('addon_parent_ids', []))
                ->where('id', '!=', $service->id)
                ->pluck('id');
            $service->parentServices()->sync($parents);
        } elseif (! $service->isAddon() && $request->has('addon_parent_ids')) {
            $service->parentServices()->detach();
        }
    }

    /** Compact representation used by the optimistic front-end. */
    private function serviceArray(Service $s): array
    {
        return [
            'id'                 => $s->id,
            'service_type'       => $s->service_type,
            'category_id'        => $s->service_category_id,
            'category_name'      => $s->serviceCategory?->localizedName(),
            'name_en'            => $s->name_en,
            'name_ar'            => $s->name_ar,
            'description'        => $s->description,
            'price'              => (float) $s->price,
            'price_to'           => $s->price_to !== null ? (float) $s->price_to : null,
            'price_type'         => $s->price_type,
            'price_label'        => $s->priceLabel(),
            'currency'           => $s->currency,
            'duration_minutes'   => $s->duration_minutes,
            'duration_label'     => $this->durationLabel($s->duration_minutes),
            'is_active'          => (bool) $s->is_active,
            'is_bookable_online' => (bool) $s->is_bookable_online,
            'badges'             => array_values(array_intersect((array) ($s->badges ?? []), Service::BADGES)),
            'is_free'            => (bool) $s->is_free,
            'requires_approval'  => (bool) $s->requires_approval,
            'sort_order'         => $s->sort_order,
            'membership_validity_days' => $s->membership_validity_days,
            'membership_sessions'      => $s->membership_sessions,
            'discount_type'      => $s->discount_type,
            'discount_value'     => $s->discount_value !== null ? (float) $s->discount_value : null,
            'discount_starts_at' => optional($s->discount_starts_at)->format('Y-m-d\TH:i'),
            'discount_ends_at'   => optional($s->discount_ends_at)->format('Y-m-d\TH:i'),
            'has_discount'       => $s->hasActiveDiscount(),
            'final_price'        => $s->finalPrice(),
            'employee_ids'       => $s->relationLoaded('employees') ? $s->employees->pluck('id')->all() : $s->employees()->pluck('employees.id')->all(),
            'resource_ids'       => $s->relationLoaded('resources') ? $s->resources->pluck('id')->all() : $s->resources()->pluck('resources.id')->all(),
            'package_items'      => $s->relationLoaded('packageItems') ? $s->packageItems->map(fn ($c) => [
                'id'          => $c->id,
                'name'        => $c->localizedName(),
                'is_optional' => (bool) $c->pivot->is_optional,
                'quantity'    => (int) $c->pivot->quantity,
            ])->values()->all() : [],
            'addon_parent_ids'   => $s->relationLoaded('parentServices')
                ? $s->parentServices->pluck('id')->all()
                : $s->parentServices()->pluck('services.id')->all(),
        ];
    }

    private function durationLabel(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        $str = trim(($h > 0 ? $h . __('h') . ' ' : '') . ($m > 0 ? $m . __('m') : ''));
        return $str !== '' ? $str : ($minutes . ' ' . __('min'));
    }
}
