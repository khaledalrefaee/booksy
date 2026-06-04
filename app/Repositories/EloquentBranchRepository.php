<?php

namespace App\Repositories;

use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class EloquentBranchRepository implements BranchRepositoryInterface
{
    public function orderedForCompany(Company $company): Collection
    {
        return Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('sort_order')
            ->orderByLocalizedName()
            ->get();
    }
}
