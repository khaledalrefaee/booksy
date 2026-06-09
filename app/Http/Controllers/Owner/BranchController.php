<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBranchRequest;
use App\Http\Requests\Owner\StoreBranchWorkingHoursRequest;
use App\Http\Requests\Owner\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\BranchImage;
use App\Models\BranchWorkingHour;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        $search = trim($request->input('q', ''));
        $perPage = 15;

        $query = Branch::query()
            ->with(['company', 'services', 'employees', 'workingHours'])
            ->orderBy('sort_order')
            ->orderByLocalizedName();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name_en', 'like', "%{$search}%")
                         ->orWhere('name_ar', 'like', "%{$search}%");
                  });
            });
        }

        $branches = $query->paginate($perPage)->withQueryString();

        return view('owner.branches.index', compact('branches', 'search'));
    }

    public function create(): View
    {
        $companies = Company::query()->orderByLocalizedName()->get();

        return view('owner.branches.create', compact('companies'));
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $companyId = (int) $data['company_id'];
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_head_office'] = $request->boolean('is_head_office');

        $branch = Branch::query()->create($data);

        if ($data['is_head_office']) {
            Branch::query()
                ->where('company_id', $companyId)
                ->whereKeyNot($branch->id)
                ->update(['is_head_office' => false]);
        }

        $this->syncImages($branch, $request->file('images', []), $request->input('image_sort_orders', []));

        return redirect()
            ->route('owner.branches.working-hours.create', $branch)
            ->with('success', __('Branch created. Now set working hours.'));
    }

    public function createWorkingHours(Branch $branch): View
    {
        $branch->load(['company', 'workingHours']);

        $weekDays = $this->weekDayLabels();

        // Key by day_of_week → shift_number for easy lookup in the view
        $existingHours = [];
        foreach ($branch->workingHours as $wh) {
            $existingHours[$wh->day_of_week][$wh->shift_number] = $wh;
        }

        return view('owner.branches.working-hours', compact('branch', 'weekDays', 'existingHours'));
    }

    public function storeWorkingHours(StoreBranchWorkingHoursRequest $request, Branch $branch): RedirectResponse
    {
        foreach ($request->validated('hours') as $hour) {
            $isOpen = ! empty($hour['is_open']);
            $day = $hour['day_of_week'];

            // Shift 1
            BranchWorkingHour::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'day_of_week' => $day, 'shift_number' => 1],
                [
                    'is_open' => $isOpen,
                    'open_time' => $isOpen ? ($hour['open_time'] ?? '09:00') : null,
                    'close_time' => $isOpen ? ($hour['close_time'] ?? '18:00') : null,
                ]
            );

            // Shift 2 — only when day is open and shift 2 is enabled
            $shift2Enabled = $isOpen && ! empty($hour['shift2_enabled']);

            if ($shift2Enabled) {
                BranchWorkingHour::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'day_of_week' => $day, 'shift_number' => 2],
                    [
                        'is_open' => true,
                        'open_time' => $hour['shift2_open_time'] ?? '14:00',
                        'close_time' => $hour['shift2_close_time'] ?? '22:00',
                    ]
                );
            } else {
                // Remove shift 2 if it was previously saved but now disabled
                BranchWorkingHour::query()
                    ->where('branch_id', $branch->id)
                    ->where('day_of_week', $day)
                    ->where('shift_number', 2)
                    ->delete();
            }
        }

        return redirect()
            ->route('owner.branches.employees.create', ['branch' => $branch, 'wizard' => 1])
            ->with('success', __('Working hours saved. Now add employees.'));
    }

    public function skipWorkingHours(Branch $branch): RedirectResponse
    {
        return redirect()
            ->route('owner.branches.employees.create', ['branch' => $branch, 'wizard' => 1])
            ->with('success', __('Branch created. You can add employees or skip for now.'));
    }

    public function edit(Branch $branch): View
    {
        $branch->load(['company', 'images']);
        $companies = Company::query()->orderByLocalizedName()->get();

        return view('owner.branches.edit', compact('branch', 'companies'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_head_office'] = $request->boolean('is_head_office');

        $branch->update($data);

        if ($data['is_head_office']) {
            Branch::query()
                ->where('company_id', $branch->company_id)
                ->whereKeyNot($branch->id)
                ->update(['is_head_office' => false]);
        }

        // Update sort orders for existing images
        foreach ($request->input('existing_sort_orders', []) as $imageId => $sortOrder) {
            BranchImage::query()
                ->where('id', $imageId)
                ->where('branch_id', $branch->id)
                ->update(['sort_order' => (int) $sortOrder]);
        }

        // Delete removed images
        foreach ($request->input('delete_images', []) as $imageId) {
            $image = BranchImage::query()
                ->where('id', $imageId)
                ->where('branch_id', $branch->id)
                ->first();

            if ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        // Add new images
        $this->syncImages($branch, $request->file('images', []), $request->input('image_sort_orders', []));

        return redirect()
            ->route('owner.branches.index')
            ->with('success', __('Branch updated successfully.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        foreach ($branch->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $branch->delete();

        return redirect()
            ->route('owner.branches.index')
            ->with('success', __('Branch deleted successfully.'));
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @param  array<int, int>  $sortOrders
     */
    private function syncImages(Branch $branch, array $files, array $sortOrders): void
    {
        foreach ($files as $index => $file) {
            $path = $file->store('branches/images', 'public');
            $branch->images()->create([
                'path' => $path,
                'sort_order' => (int) ($sortOrders[$index] ?? $index),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function weekDayLabels(): array
    {
        return [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];
    }
}
