<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchImage;
use App\Services\BranchQrService;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BranchController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    public function index(): View
    {
        $branches = $this->company()
            ->branches()
            ->orderBy('sort_order')
            ->get();

        return view('company.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $countries = \App\Models\Country::orderBy('sort_order')->get(['id', 'name_en', 'name_ar']);
        return view('company.branches.create', compact('countries'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->company()->canAddBranch()) {
            return redirect()
                ->route('company.branches.index')
                ->with('error', __('You have reached the maximum number of branches allowed by your plan.'));
        }

        $data = $request->validate([
            'name_en'         => ['required', 'string', 'max:255'],
            'name_ar'         => ['nullable', 'string', 'max:255'],
            'description_en'  => ['nullable', 'string', 'max:1000'],
            'description_ar'  => ['nullable', 'string', 'max:1000'],
            'country_id'      => ['nullable', 'integer', 'exists:countries,id'],
            'governorate_id'  => ['nullable', 'integer', 'exists:governorates,id'],
            'area_id'         => ['nullable', 'integer', 'exists:areas,id'],
            'address'         => ['nullable', 'string', 'max:500'],
            'sort_order'      => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_head_office'  => ['boolean'],
            'status'          => ['required', 'in:active,inactive,maintenance'],
            'booking_mode'    => ['required', 'in:marketplace,private'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
            'phones'          => ['nullable', 'array'],
            'phones.*'        => ['nullable', 'string', 'max:30'],
            'phone_codes'     => ['nullable', 'array'],
            'landlines'       => ['nullable', 'array'],
            'landlines.*'     => ['nullable', 'string', 'max:30'],
            'landline_codes'  => ['nullable', 'array'],
            'social_links'                    => ['nullable', 'array'],
            'social_links.*'                  => ['nullable', 'string', 'max:500'],
            'loyalty_points_per_visit'         => ['nullable', 'integer', 'min:0', 'max:9999'],
            'loyalty_points_per_extra_service' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'loyalty_points_per_currency_unit' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['loyalty_points_per_visit']         = $data['loyalty_points_per_visit'] ?? 10;
        $data['loyalty_points_per_extra_service'] = $data['loyalty_points_per_extra_service'] ?? 5;
        $data['loyalty_points_per_currency_unit'] = $data['loyalty_points_per_currency_unit'] ?? 10000;

        $this->validatePhoneDigits($request->input('phones', []), $request->input('phone_codes', []));

        $company = $this->company();

        if (! empty($data['is_head_office'])) {
            $company->branches()->update(['is_head_office' => false]);
        }

        $phones    = $this->mergeDialCodes($data['phones'] ?? [], $request->input('phone_codes', []));
        $landlines = $this->mergeDialCodes($data['landlines'] ?? [], []);

        $branch = $company->branches()->create([
            'name_en'        => $data['name_en'],
            'name_ar'        => $data['name_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'country_id'     => $data['country_id'] ?? null,
            'governorate_id' => $data['governorate_id'] ?? null,
            'area_id'        => $data['area_id'] ?? null,
            'address'        => $data['address'] ?? null,
            'phone'          => $phones[0] ?? null,
            'phones'         => count($phones) > 1 ? array_slice($phones, 1) : null,
            'landline_phone' => $landlines[0] ?? null,
            'landlines'      => count($landlines) > 1 ? array_slice($landlines, 1) : null,
            'is_head_office' => ! empty($data['is_head_office']),
            'status'         => $data['status'],
            'booking_mode'   => $data['booking_mode'],
            'sort_order'     => $data['sort_order'] ?? $company->branches()->count(),
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
        ]);

        SocialLink::syncFor($branch, $request->input('social_links', []));

        // Generate QR code
        try {
            $qrPath = (new BranchQrService())->generate($branch);
            $branch->update(['qr_code' => $qrPath]);
        } catch (\Throwable $e) {
            // QR failure must not block branch creation
        }

        return redirect()
            ->route('company.branches.working-hours.edit', $branch)
            ->with('branch_created', $branch->localizedName());
    }

    public function show(Branch $branch): View
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
        $branch->load(['workingHours']);

        $employees = $branch->employees()
            ->with([
                'role',
                'compensation',
                'serviceCommissions',
            ])
            ->withCount([
                'appointments as appointments_total',
                'appointments as appointments_this_month' => fn($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year),
                'appointments as appointments_completed_month' => fn($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year)
                    ->where('status', 'completed'),
            ])
            ->withSum(
                ['appointments as revenue_this_month' => fn($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year)
                    ->where('status', 'completed')],
                'total_price'
            )
            ->orderBy('name_en')
            ->get();

        $recentAppointments = $branch->appointments()
            ->with(['customer', 'service', 'employee'])
            ->orderByDesc('start_time')
            ->limit(8)
            ->get();

        $stats = [
            'employees'        => $employees->count(),
            'active_employees' => $employees->where('is_active', true)->count(),
            'appointments_month' => $branch->appointments()
                ->whereMonth('start_time', now()->month)
                ->whereYear('start_time', now()->year)
                ->count(),
            'revenue_month'    => $branch->appointments()
                ->whereMonth('start_time', now()->month)
                ->whereYear('start_time', now()->year)
                ->where('status', 'completed')
                ->sum('total_price'),
        ];

        return view('company.branches.show', compact('branch', 'employees', 'recentAppointments', 'stats'));
    }

    public function edit(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $countries   = \App\Models\Country::orderBy('sort_order')->get(['id', 'name_en', 'name_ar']);
        $governorates = $branch->country_id
            ? \App\Models\Governorate::where('country_id', $branch->country_id)->orderBy('sort_order')->get(['id', 'name_en', 'name_ar'])
            : collect();
        $areas = $branch->governorate_id
            ? \App\Models\Area::where('governorate_id', $branch->governorate_id)->orderBy('sort_order')->get(['id', 'name_en', 'name_ar'])
            : collect();

        $socialLinks = $branch->socialLinks()->get()->keyBy('platform');

        return view('company.branches.edit', compact('branch', 'socialLinks', 'countries', 'governorates', 'areas'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'name_en'         => ['required', 'string', 'max:255'],
            'name_ar'         => ['nullable', 'string', 'max:255'],
            'description_en'  => ['nullable', 'string', 'max:1000'],
            'description_ar'  => ['nullable', 'string', 'max:1000'],
            'country_id'      => ['nullable', 'integer', 'exists:countries,id'],
            'governorate_id'  => ['nullable', 'integer', 'exists:governorates,id'],
            'area_id'         => ['nullable', 'integer', 'exists:areas,id'],
            'address'         => ['nullable', 'string', 'max:500'],
            'sort_order'      => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_head_office'  => ['boolean'],
            'status'          => ['required', 'in:active,inactive,maintenance'],
            'booking_mode'    => ['required', 'in:marketplace,private'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
            'phones'          => ['nullable', 'array'],
            'phones.*'        => ['nullable', 'string', 'max:30'],
            'phone_codes'     => ['nullable', 'array'],
            'landlines'       => ['nullable', 'array'],
            'landlines.*'     => ['nullable', 'string', 'max:30'],
            'landline_codes'  => ['nullable', 'array'],
            'social_links'                    => ['nullable', 'array'],
            'social_links.*'                  => ['nullable', 'string', 'max:500'],
            'loyalty_points_per_visit'         => ['nullable', 'integer', 'min:0', 'max:9999'],
            'loyalty_points_per_extra_service' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'loyalty_points_per_currency_unit' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['loyalty_points_per_visit']         = $data['loyalty_points_per_visit'] ?? 0;
        $data['loyalty_points_per_extra_service'] = $data['loyalty_points_per_extra_service'] ?? 0;
        $data['loyalty_points_per_currency_unit'] = $data['loyalty_points_per_currency_unit'] ?? 0;

        $this->validatePhoneDigits($request->input('phones', []), $request->input('phone_codes', []));

        if (! empty($data['is_head_office'])) {
            $this->company()->branches()->where('id', '!=', $branch->id)->update(['is_head_office' => false]);
        }

        $phones    = $this->mergeDialCodes($data['phones'] ?? [], $request->input('phone_codes', []));
        $landlines = $this->mergeDialCodes($data['landlines'] ?? [], []);

        $branch->update([
            'name_en'        => $data['name_en'],
            'name_ar'        => $data['name_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'country_id'     => $data['country_id'] ?? null,
            'governorate_id' => $data['governorate_id'] ?? null,
            'area_id'        => $data['area_id'] ?? null,
            'address'        => $data['address'] ?? null,
            'sort_order'     => $data['sort_order'] ?? $branch->sort_order,
            'phone'          => $phones[0] ?? null,
            'phones'         => count($phones) > 1 ? array_slice($phones, 1) : null,
            'landline_phone' => $landlines[0] ?? null,
            'landlines'      => count($landlines) > 1 ? array_slice($landlines, 1) : null,
            'is_head_office' => ! empty($data['is_head_office']),
            'status'         => $data['status'],
            'booking_mode'   => $data['booking_mode'],
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
            'loyalty_points_per_visit'         => $data['loyalty_points_per_visit'],
            'loyalty_points_per_extra_service' => $data['loyalty_points_per_extra_service'],
            'loyalty_points_per_currency_unit' => $data['loyalty_points_per_currency_unit'],
        ]);

        // Sync social links
        SocialLink::syncFor($branch, $request->input('social_links', []));

        // Regenerate QR (URL stays same but logo may have changed)
        try {
            $qrPath = (new BranchQrService())->generate($branch);
            $branch->update(['qr_code' => $qrPath]);
        } catch (\Throwable $e) {
            // silent
        }

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch updated.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);
        $branch->delete();

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch deleted.'));
    }

    /** Quick status toggle from the branch list */
    public function updateStatus(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $request->validate([
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $branch->update(['status' => $request->status]);

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch status updated.'));
    }

    /**
     * Deactivate a branch and immediately end the owner's session. Called via
     * AJAX from the status control so the client can redirect to login without a
     * page refresh. Also emails the owner a notification (best-effort).
     */
    public function deactivate(Request $request, Branch $branch): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);

        $company = $branch->company;
        $branch->update(['status' => 'inactive']);
        \App\Support\Auditor::log("Deactivated branch {$branch->localizedName()} (owner signed out)", $branch);

        // Notify the owner — never let a mail failure block the sign-out.
        try {
            if (filled($company->email)) {
                \Illuminate\Support\Facades\Mail::to($company->email)
                    ->send(new \App\Mail\BranchDeactivatedNotification($company, $branch));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Branch deactivation email failed: ' . $e->getMessage());
        }

        Auth::guard('company')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('company.login')]);
    }

    public function regenerateQr(Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);
        try {
            $qrPath = (new BranchQrService())->generate($branch);
            $branch->update(['qr_code' => $qrPath]);
            return back()->with('success', __('QR code regenerated.'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('QR generation failed', [
                'branch' => $branch->id,
                'error'  => $e->getMessage(),
                'file'   => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        }
    }

    // ── Gallery ──────────────────────────────────────────────────────────────

    public function gallery(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        // The merchant sees every one of their photos (approved / pending /
        // rejected) grouped by kind, so the review state is always visible.
        $images      = $branch->images()->orderBy('sort_order')->get();
        $placeImages = $images->where('type', BranchImage::TYPE_PLACE)->values();
        $workImages  = $images->where('type', BranchImage::TYPE_WORK)->values();

        return view('company.branches.gallery', compact('branch', 'placeImages', 'workImages'));
    }

    /**
     * Upload one batch of photos, all of the same kind (place | work). Each file
     * is inspected with GD: unusable files are rejected outright and never
     * stored; clear photos are approved immediately; photos with a possible
     * quality issue are held as `pending` for GlowRez to confirm. The response
     * reports the outcome of every file so the UI can show it.
     */
    public function galleryUpload(Request $request, Branch $branch): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);

        $cfg     = config('gallery');
        $maxKb   = (int) ($cfg['max_bytes'] / 1024);
        $maxMb   = (int) round($cfg['max_bytes'] / 1048576);

        $request->validate([
            'images'   => ['required', 'array', 'max:' . $cfg['max_files']],
            'images.*' => ['required', 'file', 'max:' . $maxKb],
            'type'     => ['required', 'in:' . BranchImage::TYPE_PLACE . ',' . BranchImage::TYPE_WORK],
        ]);

        $type      = $request->input('type');
        $nextOrder = (int) $branch->images()->where('type', $type)->max('sort_order') + 1;

        $dir = "branches/{$branch->id}/gallery";
        Storage::disk('public')->makeDirectory($dir);
        $absDir = Storage::disk('public')->path($dir);

        // Hashes already in this branch — used for same-branch duplicate detection.
        $existingHashes = $branch->images()->whereNotNull('file_hash')->pluck('file_hash')->all();
        $batchHashes    = [];

        // Per-type cap: rejected photos don't count. Re-read per request so
        // chunked uploads respect the ceiling across batches.
        $maxPerType = (int) $cfg['max_per_type'];
        $remaining  = max(0, $maxPerType - $branch->images()
            ->where('type', $type)
            ->where('status', '!=', BranchImage::STATUS_REJECTED)
            ->count());

        $results = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
        $items   = [];

        foreach ($request->file('images') as $file) {
            $name = $file->getClientOriginalName();
            $real = $file->getRealPath();

            // Duplicate within the same branch (or repeated inside this batch).
            $hash = @hash_file('sha256', $real) ?: null;
            if ($hash && (in_array($hash, $existingHashes, true) || in_array($hash, $batchHashes, true))) {
                $results['rejected']++;
                $items[] = ['ok' => false, 'name' => $name, 'status' => 'rejected', 'reason' => 'duplicate'];
                continue;
            }

            // Technical + quality inspection (GD only).
            $q = \App\Support\ImageQuality::analyze($real);
            if ($q['verdict'] === \App\Support\ImageQuality::VERDICT_REJECT) {
                $results['rejected']++;
                $items[] = ['ok' => false, 'name' => $name, 'status' => 'rejected', 'reason' => $q['reason']];
                continue;
            }

            // Enforce the per-type cap (only counts photos that will be stored).
            if ($remaining <= 0) {
                $results['rejected']++;
                $items[] = ['ok' => false, 'name' => $name, 'status' => 'rejected', 'reason' => 'limit'];
                continue;
            }

            $isPending = $q['verdict'] === \App\Support\ImageQuality::VERDICT_REVIEW;
            $status    = $isPending ? BranchImage::STATUS_PENDING : BranchImage::STATUS_APPROVED;

            // Store as WebP.
            $filename = \Str::uuid() . '.webp';
            $storagePath = $dir . '/' . $filename;
            $this->convertToWebp($real, $absDir . DIRECTORY_SEPARATOR . $filename, $cfg['webp_quality']);

            $img = $branch->images()->create([
                'path'       => $storagePath,
                'type'       => $type,
                'sort_order' => $nextOrder++,
                'status'     => $status,
                'source'     => BranchImage::SOURCE_BUSINESS,
                'width'      => $q['width'],
                'height'     => $q['height'],
                'file_hash'  => $hash,
                'flags'      => $q['flags'] ?: null,
            ]);

            if ($hash) {
                $batchHashes[] = $hash;
            }
            $remaining--;
            $results[$status]++;
            $items[] = [
                'ok'     => true,
                'id'     => $img->id,
                'url'    => asset('storage/' . $storagePath),
                'type'   => $type,
                'status' => $status,
                'name'   => $name,
                'flags'  => $q['flags'],
            ];
        }

        // Keep a sensible cover: if the branch has no cover yet, promote the
        // first approved photo so the public page and marketplace stay correct.
        $this->ensureBranchHasCover($branch);

        return response()->json([
            'results' => $items,
            'summary' => $results,
            'maxMb'   => $maxMb,
        ]);
    }

    /** Promote the earliest approved photo to cover when the branch has none. */
    private function ensureBranchHasCover(Branch $branch): void
    {
        $branch->ensureHasCover();
    }

    private function convertToWebp(string $sourcePath, string $destPath, int $quality = 82): void
    {
        \App\Support\WebpImage::convert($sourcePath, $destPath, $quality);
    }

    public function galleryDelete(Request $request, Branch $branch, BranchImage $image): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($image->branch_id === $branch->id, 403);

        // Approved GlowRez-team photos can't be deleted by the business — they
        // can only request removal (handled separately).
        if (! $image->canBusinessManage()) {
            return response()->json(['ok' => false, 'locked' => true], 403);
        }

        $wasCover = $image->is_cover;

        Storage::disk('public')->delete($image->path);
        $image->delete();

        // If we just removed the cover, hand it to the next approved photo.
        if ($wasCover) {
            $this->ensureBranchHasCover($branch);
        }

        return response()->json(['ok' => true]);
    }

    /** Set an approved photo as the branch cover (exactly one cover per branch). */
    public function galleryCover(Request $request, Branch $branch, BranchImage $image): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($image->branch_id === $branch->id, 403);

        if (! $image->isApproved()) {
            return response()->json(['ok' => false, 'reason' => 'not_approved'], 422);
        }

        $branch->images()->where('is_cover', true)->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * The business can't touch approved team photos directly, but it can flag
     * one for the GlowRez team to remove or replace. The request is recorded on
     * the photo so it surfaces in the owner review queue.
     */
    public function galleryRequestRemoval(Request $request, Branch $branch, BranchImage $image): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($image->branch_id === $branch->id, 403);

        $flags = $image->flags ?? [];
        $flags['removal_requested_at'] = now()->toISOString();
        $image->update(['flags' => $flags]);

        return response()->json(['ok' => true]);
    }

    public function galleryReorder(Request $request, Branch $branch): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);

        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        foreach ($request->order as $position => $imageId) {
            $branch->images()->where('id', $imageId)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    /**
     * دمج كود الدولة مع أرقام الجوال وتنظيف الفارغة.
     * الأرضي يُحفظ كما هو بدون كود (يمرّر codes=[]).
     */
    private function mergeDialCodes(array $numbers, array $codes, string $default = '+963'): array
    {
        $result = [];
        foreach ($numbers as $i => $num) {
            $num = trim($num);
            if ($num === '') continue;
            if (empty($codes)) {
                $result[] = $num;
            } else {
                $code = trim($codes[$i] ?? $default);
                $result[] = str_starts_with($num, '+') ? $num : $code . $num;
            }
        }
        return array_values($result);
    }

    /**
     * التحقق من عدد أرقام الجوال بناءً على كود الدولة.
     * يرمي ValidationException عند وجود خطأ.
     */
    private function validatePhoneDigits(array $numbers, array $codes): void
    {
        $dialRules = config('booksy.dial_codes');
        $default   = config('booksy.default_dial_code', '+963');
        $errors    = [];

        foreach ($numbers as $i => $num) {
            $num = trim($num);
            if ($num === '') continue;

            $code  = trim($codes[$i] ?? $default);
            $rules = $dialRules[$code] ?? null;
            if (! $rules) continue;

            /* إذا الرقم يحتوي dial code مدمج (يبدأ بـ +) نتجاهله — سبق التحقق منه */
            if (str_starts_with($num, '+')) continue;

            /* استخرج الأرقام فقط (بدون مسافات أو شرطات) */
            $digits = preg_replace('/\D/', '', $num);
            $min    = $rules['digits_min'];
            $max    = $rules['digits_max'];

            if (strlen($digits) < $min || strlen($digits) > $max) {
                $country = app()->getLocale() === 'ar'
                    ? $rules['name_ar']
                    : $rules['name_en'];

                $errors["phones.{$i}"] = $min === $max
                    ? __('Phone number for :country must be exactly :n digits.', ['country' => $country, 'n' => $min])
                    : __('Phone number for :country must be between :min and :max digits.', ['country' => $country, 'min' => $min, 'max' => $max]);
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }
}
