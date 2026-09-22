<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchImage;
use App\Models\Company;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhotoReviewController extends Controller
{
    /** The review queue, with status tabs and filters. */
    public function index(Request $request): View
    {
        $status = in_array($request->get('status'), ['all', 'pending', 'approved', 'rejected'], true)
            ? $request->get('status')
            : 'pending';

        $companyId = $request->integer('company') ?: null;
        $branchId  = $request->integer('branch') ?: null;
        $type      = in_array($request->get('type'), [BranchImage::TYPE_PLACE, BranchImage::TYPE_WORK], true) ? $request->get('type') : null;
        $source    = in_array($request->get('source'), [BranchImage::SOURCE_BUSINESS, BranchImage::SOURCE_TEAM], true) ? $request->get('source') : null;
        $search    = trim((string) $request->get('q', ''));

        $query = BranchImage::query()->with('branch.company');

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('type', $type);
        }
        if ($source) {
            $query->where('source', $source);
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($companyId) {
            $query->whereHas('branch', fn ($q) => $q->where('company_id', $companyId));
        }
        if ($search !== '') {
            $query->whereHas('branch', function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($c) use ($search) {
                      $c->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%");
                  });
            });
        }

        // Removal requests first (they need attention), then newest.
        $images = $query
            ->orderByRaw("CASE WHEN JSON_EXTRACT(flags, '$.removal_requested_at') IS NOT NULL THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $counts = BranchImage::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $removalCount = BranchImage::whereNotNull('flags')
            ->whereRaw("JSON_EXTRACT(flags, '$.removal_requested_at') IS NOT NULL")
            ->count();

        $companies = Company::orderByLocalizedName()->get(['id', 'name_en', 'name_ar']);

        // Branches grouped by company, for the "upload as team" picker.
        $branches = \App\Models\Branch::with('company:id,name_en,name_ar')
            ->orderByLocalizedName()
            ->get(['id', 'name_en', 'name_ar', 'company_id'])
            ->groupBy('company_id');

        return view('owner.photo-reviews.index', [
            'images'       => $images,
            'status'       => $status,
            'counts'       => $counts,
            'removalCount' => $removalCount,
            'companies'    => $companies,
            'branches'     => $branches,
            'filters'      => compact('companyId', 'branchId', 'type', 'source', 'search'),
            'reasons'      => config('gallery.rejection_reasons', []),
        ]);
    }

    /**
     * The GlowRez team uploads photos on behalf of a branch. These are trusted:
     * a valid image is approved immediately (source = glowrez_team) and the
     * business cannot edit or delete it, only request its removal.
     */
    public function teamStore(Request $request): RedirectResponse
    {
        $cfg = config('gallery');

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'type'      => ['required', Rule::in([BranchImage::TYPE_PLACE, BranchImage::TYPE_WORK])],
            'images'    => ['required', 'array', 'max:' . $cfg['max_files']],
            'images.*'  => ['required', 'file', 'max:' . (int) ($cfg['max_bytes'] / 1024)],
        ]);

        $branch = \App\Models\Branch::findOrFail($data['branch_id']);
        $type   = $data['type'];

        $dir = "branches/{$branch->id}/gallery";
        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($dir);
        $absDir = \Illuminate\Support\Facades\Storage::disk('public')->path($dir);

        $nextOrder = (int) $branch->images()->where('type', $type)->max('sort_order') + 1;
        $existingHashes = $branch->images()->whereNotNull('file_hash')->pluck('file_hash')->all();

        $remaining = max(0, (int) $cfg['max_per_type'] - $branch->images()
            ->where('type', $type)
            ->where('status', '!=', BranchImage::STATUS_REJECTED)
            ->count());

        $saved = 0;
        $skipped = 0;

        foreach ($request->file('images') as $file) {
            if ($remaining <= 0) {
                $skipped++;
                continue;
            }

            $real = $file->getRealPath();
            $hash = @hash_file('sha256', $real) ?: null;

            if ($hash && in_array($hash, $existingHashes, true)) {
                $skipped++;
                continue;
            }

            $q = \App\Support\ImageQuality::analyze($real);
            if ($q['verdict'] === \App\Support\ImageQuality::VERDICT_REJECT) {
                $skipped++;
                continue;
            }

            $filename = \Str::uuid() . '.webp';
            $storagePath = $dir . '/' . $filename;
            \App\Support\WebpImage::convert($real, $absDir . DIRECTORY_SEPARATOR . $filename, $cfg['webp_quality']);

            $img = $branch->images()->create([
                'path'        => $storagePath,
                'type'        => $type,
                'sort_order'  => $nextOrder++,
                'status'      => BranchImage::STATUS_APPROVED,   // team photos are trusted
                'source'      => BranchImage::SOURCE_TEAM,
                'reviewed_at' => now(),
                'reviewed_by' => auth('owner')->id(),
                'width'       => $q['width'],
                'height'      => $q['height'],
                'file_hash'   => $hash,
                'flags'       => $q['flags'] ?: null,
            ]);

            if ($hash) {
                $existingHashes[] = $hash;
            }
            OwnerAudit::record('branch-photo.team-upload', $img, label: $this->label($img));
            $remaining--;
            $saved++;
        }

        $branch->ensureHasCover();

        if ($saved > 0) {
            return back()->with('success', __(':count photos added.', ['count' => $saved]));
        }

        return back()->with('error', __('No photos were added.'));
    }

    public function approve(BranchImage $image): RedirectResponse
    {
        $image->update([
            'status'           => BranchImage::STATUS_APPROVED,
            'rejection_reason' => null,
            'reviewed_at'      => now(),
            'reviewed_by'      => auth('owner')->id(),
        ]);

        $image->branch->ensureHasCover();

        OwnerAudit::record('branch-photo.approved', $image, label: $this->label($image));

        return back()->with('success', __('Photo approved.'));
    }

    public function reject(Request $request, BranchImage $image): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(config('gallery.rejection_reasons', [])))],
        ]);

        $wasCover = $image->is_cover;

        $image->update([
            'status'           => BranchImage::STATUS_REJECTED,
            'rejection_reason' => $data['reason'],
            'is_cover'         => false,
            'reviewed_at'      => now(),
            'reviewed_by'      => auth('owner')->id(),
        ]);

        if ($wasCover) {
            $image->branch->ensureHasCover();
        }

        OwnerAudit::record('branch-photo.rejected', $image, new: ['reason' => $data['reason']], label: $this->label($image));

        return back()->with('success', __('Photo rejected.'));
    }

    /** Permanently remove a photo (e.g. an approved team photo the business asked to drop). */
    public function destroy(BranchImage $image): RedirectResponse
    {
        $branch   = $image->branch;
        $wasCover = $image->is_cover;

        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        OwnerAudit::record('branch-photo.deleted', $image, label: $this->label($image));
        $image->delete();

        if ($wasCover && $branch) {
            $branch->ensureHasCover();
        }

        return back()->with('success', __('Photo deleted.'));
    }

    /** Set an approved photo as its branch cover. */
    public function cover(BranchImage $image): RedirectResponse
    {
        if (! $image->isApproved()) {
            return back()->with('error', __('Only approved photos can be set as cover.'));
        }

        $image->branch->images()->where('is_cover', true)->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        OwnerAudit::record('branch-photo.cover', $image, label: $this->label($image));

        return back()->with('success', __('Cover updated.'));
    }

    private function label(BranchImage $image): string
    {
        $branch = $image->branch;
        $company = $branch?->company;

        $parts = array_filter([$company?->localizedName(), $branch?->localizedName()]);

        return $parts ? implode(' - ', $parts) : "Photo #{$image->id}";
    }
}
