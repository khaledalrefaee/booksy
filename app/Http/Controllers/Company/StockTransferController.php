<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\InventoryService;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    private function operatorName(): string
    {
        return $this->company()->localizedName();
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $query = StockTransfer::where('company_id', $company->id)
            ->with(['fromBranch', 'toBranch', 'items.product'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $transfers = $query->paginate(20)->withQueryString();
        $branches = $company->branches()->orderBy('sort_order')->get();

        return view('company.inventory.transfers.index', compact('transfers', 'branches'));
    }

    public function create(): View
    {
        $company = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $products = Product::where('company_id', $company->id)
            ->where('track_stock', true)
            ->where('is_active', true)
            ->with('branchStocks')
            ->orderBy('name_en')
            ->get();

        return view('company.inventory.transfers.create', compact('branches', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'from_branch_id' => ['required', 'exists:branches,id'],
            'to_branch_id' => ['required', 'exists:branches,id', 'different:from_branch_id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        abort_unless($company->branches()->where('id', $data['from_branch_id'])->exists(), 403);
        abort_unless($company->branches()->where('id', $data['to_branch_id'])->exists(), 403);

        $transfer = DB::transaction(function () use ($data, $company) {
            $transfer = StockTransfer::create([
                'company_id' => $company->id,
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'status' => 'shipped',
                'shipped_at' => now(),
                'notes' => $data['notes'] ?? null,
                'created_by_name' => $this->operatorName(),
            ]);

            $inventory = app(InventoryService::class);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                abort_unless($product->company_id === $company->id, 403);

                $transfer->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                ]);

                $inventory->removeStock(
                    $product, $data['from_branch_id'], $item['quantity'],
                    'transfer_out', "TRF-{$transfer->id}",
                    $data['to_branch_id'], null, $this->operatorName()
                );
            }

            return $transfer;
        });

        Auditor::log("Created stock transfer #{$transfer->id}", $transfer);

        return redirect()->route('company.inventory.transfers.index')
            ->with('success', __('Transfer created and shipped.'));
    }

    public function receive(Request $request, StockTransfer $transfer): RedirectResponse
    {
        abort_unless($transfer->company_id === $this->company()->id, 403);
        abort_unless($transfer->status === 'shipped', 422);

        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'exists:stock_transfer_items,id'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($transfer, $data) {
            $inventory = app(InventoryService::class);
            $receivedMap = collect($data['items'] ?? [])->keyBy('id');

            foreach ($transfer->items as $item) {
                $received = $receivedMap->has($item->id)
                    ? (int) $receivedMap[$item->id]['received_quantity']
                    : $item->quantity;

                $item->update(['received_quantity' => $received]);

                if ($received > 0) {
                    $inventory->addStock(
                        $item->product, $transfer->to_branch_id, $received,
                        'transfer_in', null, "TRF-{$transfer->id}",
                        $transfer->from_branch_id, null, $this->operatorName()
                    );
                }
            }

            $transfer->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by_name' => $this->operatorName(),
            ]);
        });

        Auditor::log("Received stock transfer #{$transfer->id}", $transfer);

        return back()->with('success', __('Transfer received.'));
    }

    public function cancel(StockTransfer $transfer): RedirectResponse
    {
        abort_unless($transfer->company_id === $this->company()->id, 403);
        abort_unless($transfer->isPending() || $transfer->status === 'shipped', 422);

        DB::transaction(function () use ($transfer) {
            if ($transfer->status === 'shipped') {
                $inventory = app(InventoryService::class);
                foreach ($transfer->items as $item) {
                    $inventory->addStock(
                        $item->product, $transfer->from_branch_id, $item->quantity,
                        'return', "TRF-{$transfer->id}-CANCEL",
                        $transfer->to_branch_id,
                        __('Transfer cancelled — stock returned'),
                        $this->operatorName()
                    );
                }
            }

            $transfer->update(['status' => 'cancelled']);
        });

        Auditor::log("Cancelled stock transfer #{$transfer->id}", $transfer);

        return back()->with('success', __('Transfer cancelled.'));
    }
}
