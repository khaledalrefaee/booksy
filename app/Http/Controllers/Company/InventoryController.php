<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchPayment;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    private function operatorName(): string
    {
        return $this->company()->localizedName();
    }

    // ── Products CRUD ──────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $company = $this->company();

        $query = Product::where('company_id', $company->id)
            ->with(['category', 'branchStocks']);

        if ($request->filled('category_id')) {
            $query->where('product_category_id', $request->input('category_id'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q
                ->where('name_en', 'like', "%$s%")
                ->orWhere('name_ar', 'like', "%$s%")
            );
        }
        if ($request->filled('branch_id')) {
            $branchId = $request->input('branch_id');
            $query->whereHas('branchStocks', fn($q) => $q->where('branch_id', $branchId));
        }

        $products = $query->orderBy('sort_order')->paginate(20)->withQueryString();
        $categories = ProductCategory::where('company_id', $company->id)->orderBy('sort_order')->get();
        $branches = $company->branches()->orderBy('sort_order')->get();

        return view('company.inventory.index', compact('products', 'categories', 'branches'));
    }

    public function create(): View
    {
        $company = $this->company();
        $categories = ProductCategory::where('company_id', $company->id)->orderBy('sort_order')->get();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $currencies = config('booksy.currencies', []);

        return view('company.inventory.create', compact('categories', 'branches', 'currencies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'unit' => ['nullable', 'string', 'max:32'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            // Initial stock per branch
            'initial_stock' => ['nullable', 'array'],
            'initial_stock.*.branch_id' => ['required', 'exists:branches,id'],
            'initial_stock.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $company = $this->company();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'company_id' => $company->id,
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'] ?? null,
            'product_category_id' => $data['product_category_id'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? 0,
            'currency' => $data['currency'],
            'unit' => $data['unit'] ?? 'piece',
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
            'track_stock' => $data['track_stock'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'is_display_only' => false,
            'image' => $imagePath,
        ]);

        if (!empty($data['initial_stock'])) {
            $inventory = app(InventoryService::class);
            foreach ($data['initial_stock'] as $stock) {
                if ($stock['quantity'] > 0) {
                    $inventory->addStock(
                        $product, $stock['branch_id'], $stock['quantity'],
                        'purchase', $product->cost_price, null, null,
                        __('Initial stock'), $this->operatorName()
                    );
                }
            }
        }

        Auditor::log("Created product: {$product->localizedName()}", $product);

        return redirect()->route('company.inventory.index')
            ->with('success', __('Product created.'));
    }

    public function edit(Product $product): View
    {
        abort_unless($product->company_id === $this->company()->id, 403);

        $categories = ProductCategory::where('company_id', $this->company()->id)->orderBy('sort_order')->get();
        $branches = $this->company()->branches()->orderBy('sort_order')->get();
        $currencies = config('booksy.currencies', []);
        $stocks = BranchStock::where('product_id', $product->id)->get()->keyBy('branch_id');

        return view('company.inventory.edit', compact('product', 'categories', 'branches', 'currencies', 'stocks'));
    }

    public function show(Product $product): \Illuminate\View\View
    {
        abort_unless($product->company_id === $this->company()->id, 403);
        $product->load(['category', 'branchStocks.branch']);
        $branches = $this->company()->branches()->orderBy('sort_order')->get();
        $stocks   = BranchStock::where('product_id', $product->id)->get()->keyBy('branch_id');
        $movements = $product->stockMovements()->latest('created_at')->limit(20)->get();
        return view('company.inventory.show', compact('product', 'branches', 'stocks', 'movements'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'unit' => ['nullable', 'string', 'max:32'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }

        $data['is_display_only'] = false;
        $product->update($data);

        // Adjust stock per branch if provided
        if ($request->filled('stock') && $product->track_stock) {
            $inventory = app(InventoryService::class);
            foreach ($request->input('stock', []) as $branchId => $qty) {
                $branch = \App\Models\Branch::find($branchId);
                if ($branch && $branch->company_id === $this->company()->id) {
                    $inventory->adjustStock($product, $branchId, (int) $qty, __('Manual adjustment'), $this->operatorName());
                }
            }
        }

        Auditor::log("Updated product: {$product->localizedName()}", $product);

        return redirect()->route('company.inventory.show', $product)
            ->with('success', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($product->company_id === $this->company()->id, 403);

        Auditor::log("Deleted product: {$product->localizedName()}", $product);
        $product->delete();

        return redirect()->route('company.inventory.index')
            ->with('success', __('Product deleted.'));
    }

    // ── Stock Management ────────────────────────────────────────────────────

    public function stock(Request $request): View
    {
        $company = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $branchId = $request->input('branch_id', $branches->first()?->id);

        $stocks = BranchStock::where('branch_id', $branchId)
            ->with(['product.category'])
            ->get()
            ->sortBy('product.name_en');

        $lowStockCount = $stocks->filter(fn($s) => $s->isLow())->count();

        return view('company.inventory.stock', compact('branches', 'branchId', 'stocks', 'lowStockCount'));
    }

    public function addStock(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless($branch->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        abort_unless($product->company_id === $this->company()->id, 403);

        app(InventoryService::class)->addStock(
            $product, $branch->id, $data['quantity'],
            'purchase', $data['unit_cost'] ?? null, null, null,
            $data['notes'] ?? null, $this->operatorName()
        );

        Auditor::log("Added {$data['quantity']} × {$product->localizedName()} to {$branch->localizedName()}", $product);

        return back()->with('success', __('Stock added.'));
    }

    public function removeStock(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless($branch->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        abort_unless($product->company_id === $this->company()->id, 403);

        try {
            app(InventoryService::class)->removeStock(
                $product, $branch->id, $data['quantity'],
                'manual', null, null,
                $data['notes'] ?? null, $this->operatorName()
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        Auditor::log("Removed {$data['quantity']} × {$product->localizedName()} from {$branch->localizedName()}", $product);

        return back()->with('success', __('Stock removed.'));
    }

    public function adjustStock(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless($branch->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'new_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        abort_unless($product->company_id === $this->company()->id, 403);

        app(InventoryService::class)->adjustStock(
            $product, $branch->id, $data['new_quantity'],
            $data['notes'] ?? null, $this->operatorName()
        );

        Auditor::log("Adjusted stock for {$product->localizedName()} in {$branch->localizedName()} to {$data['new_quantity']}", $product);

        return back()->with('success', __('Stock adjusted.'));
    }

    // ── Sell Product ────────────────────────────────────────────────────────

    public function sell(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless($branch->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer'],
            'sold_by_employee_id' => ['nullable', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($data['sold_by_employee_id'])) {
            $seller = \App\Models\Employee::find($data['sold_by_employee_id']);
            abort_unless($seller && $seller->company_id === $this->company()->id, 403);
        }

        $product = Product::findOrFail($data['product_id']);
        abort_unless($product->company_id === $this->company()->id, 403);

        $company = $this->company();
        $totalPrice = (float) $product->price * $data['quantity'];

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($product, $branch, $data, $company, $totalPrice) {
                if ($product->track_stock) {
                    app(InventoryService::class)->removeStock(
                        $product, $branch->id, $data['quantity'],
                        'sale', null, null,
                        $data['notes'] ?? null, $this->operatorName()
                    );
                }

            BranchPayment::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'type' => 'income',
                'category' => 'product_sale',
                'amount' => $totalPrice,
                'currency' => $product->currency,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $product->localizedName() . ' × ' . $data['quantity'],
                'recorded_by_employee_id' => $data['sold_by_employee_id'] ?? null,
                'paid_at' => now(),
            ]);
        });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        Auditor::log("Sold {$data['quantity']} × {$product->localizedName()} — {$totalPrice} {$product->currency}", $product);

        return back()->with('success', __('Product sale recorded in cash register.'));
    }

    // ── Movement History ────────────────────────────────────────────────────

    public function deleteMovements(Request $request): RedirectResponse
    {
        $company = $this->company();
        $ids = $request->input('ids', []);

        if ($request->input('delete_all')) {
            StockMovement::where('company_id', $company->id)->delete();
            return back()->with('success', __('All movements deleted.'));
        }

        if (!empty($ids)) {
            StockMovement::where('company_id', $company->id)->whereIn('id', $ids)->delete();
            return back()->with('success', __('Selected movements deleted.'));
        }

        return back()->with('warning', __('No movements selected.'));
    }

    public function movements(Request $request): View
    {
        $company = $this->company();

        $query = StockMovement::where('company_id', $company->id)
            ->with(['product', 'branch', 'relatedBranch'])
            ->orderByDesc('created_at');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $movements = $query->paginate(10)->withQueryString();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $products = Product::where('company_id', $company->id)->orderBy('name_en')->get();

        return view('company.inventory.movements', compact('movements', 'branches', 'products'));
    }
}
