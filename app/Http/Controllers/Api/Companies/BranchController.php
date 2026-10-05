<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Api\ApiController;
use App\Models\Branch;
use App\Support\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The branches of the signed-in company (resolved from the bearer token).
 *
 * The app calls this first: the branch picker, and the `branch_id` the booking
 * endpoints (appointments/staff-events, branch-data, …) need, come from here.
 * Same list and order the web sidebar's branch selector uses.
 */
class BranchController extends ApiController
{
    public function index(Request $request, BranchContext $context): JsonResponse
    {
        $company  = $request->attributes->get('company');
        $branches = $context->accessibleBranches($company);

        // Preselect the head office, else the first branch.
        $default = $branches->firstWhere('is_head_office', true) ?? $branches->first();

        return $this->success([
            'default_branch_id' => $default?->id,
            'branches'          => $branches->map(fn (Branch $b) => $this->present($b))->values(),
        ]);
    }

    /** The public shape of a branch returned to the app. */
    private function present(Branch $b): array
    {
        return [
            'id'             => $b->id,
            'name'           => $b->localizedName(),
            'name_en'        => $b->name_en,
            'name_ar'        => $b->name_ar,
            'slug'           => $b->slug,
            'status'         => $b->status,
            'is_head_office' => (bool) $b->is_head_office,
            'phone'          => $b->phone,
            'address'        => $b->address,
            // The branch clock — appointment times are branch wall-clock.
            'timezone'       => $b->tz(),
            'time_format'    => $b->uses24HourClock() ? '24h' : '12h',
            'interval'       => (int) ($b->appointment_interval ?: 15),
            'first_day'      => (int) ($b->first_day_of_week ?? 0),
        ];
    }
}
