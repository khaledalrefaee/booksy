<?php

namespace App\Services;

use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StaffNotification;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function addStock(
        Product $product,
        int $branchId,
        int $quantity,
        string $type = 'purchase',
        ?float $unitCost = null,
        ?string $reference = null,
        ?int $relatedBranchId = null,
        ?string $notes = null,
        ?string $actorName = null,
    ): BranchStock {
        return DB::transaction(function () use ($product, $branchId, $quantity, $type, $unitCost, $reference, $relatedBranchId, $notes, $actorName) {
            $stock = BranchStock::lockForUpdate()
                ->firstOrCreate(
                    ['branch_id' => $branchId, 'product_id' => $product->id],
                    ['quantity' => 0]
                );

            $before = $stock->quantity;
            $stock->increment('quantity', $quantity);

            StockMovement::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $before + $quantity,
                'unit_cost' => $unitCost,
                'currency' => $product->currency,
                'reference' => $reference,
                'related_branch_id' => $relatedBranchId,
                'notes' => $notes,
                'created_by_name' => $actorName,
                'created_at' => now(),
            ]);

            return $stock->fresh();
        });
    }

    public function removeStock(
        Product $product,
        int $branchId,
        int $quantity,
        string $type = 'sale',
        ?string $reference = null,
        ?int $relatedBranchId = null,
        ?string $notes = null,
        ?string $actorName = null,
    ): BranchStock {
        return DB::transaction(function () use ($product, $branchId, $quantity, $type, $reference, $relatedBranchId, $notes, $actorName) {
            $stock = BranchStock::lockForUpdate()
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->firstOrFail();

            abort_if($stock->quantity < $quantity, 422, __('Insufficient stock. Available: :qty', ['qty' => $stock->quantity]));

            $before = $stock->quantity;
            $stock->decrement('quantity', $quantity);

            StockMovement::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'type' => $type,
                'quantity' => -$quantity,
                'quantity_before' => $before,
                'quantity_after' => $before - $quantity,
                'reference' => $reference,
                'related_branch_id' => $relatedBranchId,
                'notes' => $notes,
                'created_by_name' => $actorName,
                'created_at' => now(),
            ]);

            $stock->refresh();
            $this->checkLowStock($product, $branchId, $stock->quantity);

            return $stock;
        });
    }

    public function adjustStock(
        Product $product,
        int $branchId,
        int $newQuantity,
        ?string $notes = null,
        ?string $actorName = null,
    ): BranchStock {
        return DB::transaction(function () use ($product, $branchId, $newQuantity, $notes, $actorName) {
            $stock = BranchStock::lockForUpdate()
                ->firstOrCreate(
                    ['branch_id' => $branchId, 'product_id' => $product->id],
                    ['quantity' => 0]
                );

            $before = $stock->quantity;
            $diff = $newQuantity - $before;

            if ($diff === 0) return $stock;

            $stock->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'type' => 'adjustment',
                'quantity' => $diff,
                'quantity_before' => $before,
                'quantity_after' => $newQuantity,
                'notes' => $notes,
                'created_by_name' => $actorName,
                'created_at' => now(),
            ]);

            if ($diff < 0) {
                $this->checkLowStock($product, $branchId, $newQuantity);
            }

            return $stock;
        });
    }

    private function checkLowStock(Product $product, int $branchId, int $currentQty): void
    {
        if (!$product->track_stock) return;
        if ($currentQty > $product->low_stock_threshold) return;

        $branch = \App\Models\Branch::find($branchId);
        $productName = $product->localizedName();
        $branchName = $branch?->localizedName() ?? '#' . $branchId;

        StaffNotification::create([
            'company_id' => $product->company_id,
            'branch_id' => $branchId,
            'type' => 'low_stock',
            'title' => __('Low stock alert'),
            'body' => __(':product has only :qty left in :branch', [
                'product' => $productName,
                'qty' => $currentQty,
                'branch' => $branchName,
            ]),
            'icon' => '📦',
            'color' => '#ef4444',
            'data' => [
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'quantity' => $currentQty,
                'threshold' => $product->low_stock_threshold,
            ],
        ]);
    }
}
