<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    public function index(Request $request)
    {
        $query = ProductCategory::where('company_id', $this->company()->id)
            ->withCount('products')
            ->with('parent')
            ->orderBy('sort_order');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        $categories = $query->paginate(12)->withQueryString();

        $stats = [
            'total'       => ProductCategory::where('company_id', $this->company()->id)->count(),
            'parents'     => ProductCategory::where('company_id', $this->company()->id)->whereNull('parent_id')->count(),
            'subs'        => ProductCategory::where('company_id', $this->company()->id)->whereNotNull('parent_id')->count(),
            'products'    => \App\Models\Product::where('company_id', $this->company()->id)->count(),
        ];

        $allCategories = ProductCategory::where('company_id', $this->company()->id)
            ->whereNull('parent_id')->orderBy('name_en')->get();

        return view('company.inventory.categories.index', compact('categories', 'stats', 'allCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en'   => ['required', 'string', 'min:2', 'max:255'],
            'name_ar'   => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'color'     => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        if (!empty($data['parent_id'])) {
            $parent = ProductCategory::find($data['parent_id']);
            abort_unless($parent && $parent->company_id === $this->company()->id, 403);
        }

        $cat = ProductCategory::create([
            'company_id' => $this->company()->id,
            'color'      => $data['color'] ?? '#667eea',
            ...$data,
        ]);

        Auditor::log("Created product category: {$cat->localizedName()}", $cat);

        return back()->with('success', __('Category created.'));
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        abort_unless($productCategory->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'name_en'   => ['required', 'string', 'min:2', 'max:255'],
            'name_ar'   => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:product_categories,id', 'different:' . $productCategory->id],
            'color'     => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        if (!empty($data['parent_id'])) {
            $parent = ProductCategory::find($data['parent_id']);
            abort_unless($parent && $parent->company_id === $this->company()->id, 403);
        }

        $productCategory->update($data);

        Auditor::log("Updated product category: {$productCategory->localizedName()}", $productCategory);

        return back()->with('success', __('Category updated.'));
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        abort_unless($productCategory->company_id === $this->company()->id, 403);

        $name = $productCategory->localizedName();
        $productCategory->products()->update(['product_category_id' => null]);
        $productCategory->children()->update(['parent_id' => null]);
        $productCategory->delete();

        Auditor::log("Deleted product category: {$name}");

        return back()->with('success', __('Category deleted.'));
    }
}
