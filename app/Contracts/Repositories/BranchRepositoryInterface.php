<?php

namespace App\Contracts\Repositories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

interface BranchRepositoryInterface
{
    /**
     * @return Collection<int, Branch>
     */
    public function orderedForCompany(Company $company): Collection;
}
