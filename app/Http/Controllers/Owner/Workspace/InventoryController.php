<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockTransfer;

/**
 * Company Workspace — Inventory tab (products + stock + transfers + categories).
 * Monitoring view; stock mutations (movements) defer to the full editor.
 */
class InventoryController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        if (! $company->hasFeature('inventory')) {
            return $this->tab('inventory', $company, ['locked' => true]);
        }

        $products = Product::query()
            ->where('company_id', $company->id)
            ->with(['category', 'branchStocks'])
            ->orderBy('sort_order')
            ->get();

        $transfers = StockTransfer::query()
            ->where('company_id', $company->id)
            ->with(['fromBranch', 'toBranch'])
            ->latest('id')
            ->limit(50)
            ->get();

        $categories = ProductCategory::query()
            ->where('company_id', $company->id)
            ->orderBy('sort_order')
            ->get();

        return $this->tab('inventory', $company, [
            'locked'     => false,
            'products'   => $products,
            'transfers'  => $transfers,
            'categories' => $categories,
        ]);
    }
}
