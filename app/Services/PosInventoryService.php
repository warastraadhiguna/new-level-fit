<?php

namespace App\Services;

use App\Models\Pos\BranchProduct;
use App\Models\Pos\InventoryMovement;
use App\Models\Pos\Purchase;
use App\Models\Pos\Sale;
use App\Models\Pos\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosInventoryService
{
    public function receivePurchase(Purchase $purchase, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === 'received') {
                return $purchase;
            }

            if ($purchase->status !== 'draft') {
                throw ValidationException::withMessages([
                    'purchase' => 'Hanya pembelian berstatus Draft yang dapat diterima.',
                ]);
            }

            $items = $purchase->items()->orderBy('product_id')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['purchase' => 'Pembelian tidak memiliki item.']);
            }

            foreach ($items as $item) {
                $branchProduct = $this->lockedBranchProduct($purchase->branch_store_id, $item->product_id);
                $before = (float) $branchProduct->stock_qty;
                $quantity = (float) $item->quantity;
                $cost = (float) $item->unit_cost;
                $after = $before + $quantity;
                $oldValue = $before * (float) $branchProduct->average_cost;
                $newAverage = $after > 0 ? (($oldValue + ($quantity * $cost)) / $after) : $cost;

                $branchProduct->update([
                    'stock_qty' => $after,
                    'average_cost' => round($newAverage, 2),
                ]);

                $this->movement(
                    $purchase->branch_store_id,
                    $item->product_id,
                    $userId,
                    'purchase',
                    $before,
                    $quantity,
                    $after,
                    $cost,
                    'purchase',
                    $purchase->id,
                    'Penerimaan ' . $purchase->purchase_number
                );
            }

            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            return $purchase->fresh(['items.product', 'supplier']);
        }, 3);
    }

    public function checkout(array $data, int $branchId, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $branchId, $userId) {
            $existing = Sale::where('idempotency_key', $data['idempotency_key'])
                ->where('branch_store_id', $branchId)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing->load(['items', 'payments.methodPayment', 'cashier']);
            }

            $requestedItems = collect($data['items'])
                ->groupBy('product_id')
                ->map(function ($rows, $productId) {
                    return [
                        'product_id' => (int) $productId,
                        'quantity' => $rows->sum(function ($row) {
                            return (float) $row['quantity'];
                        }),
                    ];
                })
                ->sortBy('product_id')
                ->values();

            $lines = [];
            $subtotal = 0;
            foreach ($requestedItems as $requested) {
                $branchProduct = BranchProduct::with('product')
                    ->where('branch_store_id', $branchId)
                    ->where('product_id', $requested['product_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$branchProduct || !$branchProduct->product || !$branchProduct->product->is_active) {
                    throw ValidationException::withMessages(['items' => 'Salah satu produk tidak aktif di cabang ini.']);
                }

                $quantity = (float) $requested['quantity'];
                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['items' => 'Jumlah produk harus lebih dari nol.']);
                }
                if ((float) $branchProduct->stock_qty < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok ' . $branchProduct->product->name . ' tidak mencukupi.',
                    ]);
                }

                $lineSubtotal = $quantity * (float) $branchProduct->selling_price;
                $subtotal += $lineSubtotal;
                $lines[] = compact('branchProduct', 'quantity', 'lineSubtotal');
            }

            $discount = (float) ($data['discount_amount'] ?? 0);
            if ($discount < 0 || $discount > $subtotal) {
                throw ValidationException::withMessages(['discount_amount' => 'Diskon tidak valid.']);
            }

            $grandTotal = $subtotal - $discount;
            $paid = (float) $data['paid_amount'];
            if ($paid < $grandTotal) {
                throw ValidationException::withMessages(['paid_amount' => 'Nominal pembayaran kurang.']);
            }

            $sale = Sale::create([
                'branch_store_id' => $branchId,
                'cashier_id' => $userId,
                'sale_number' => $this->number('POS', 'pos_sales'),
                'customer_name' => $data['customer_name'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'change_amount' => $paid - $grandTotal,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
            ]);

            foreach ($lines as $line) {
                /** @var BranchProduct $branchProduct */
                $branchProduct = $line['branchProduct'];
                $before = (float) $branchProduct->stock_qty;
                $after = $before - $line['quantity'];

                $sale->items()->create([
                    'product_id' => $branchProduct->product_id,
                    'product_name' => $branchProduct->product->name,
                    'sku' => $branchProduct->product->sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $branchProduct->selling_price,
                    'unit_cost' => $branchProduct->average_cost,
                    'subtotal' => $line['lineSubtotal'],
                ]);
                $branchProduct->update(['stock_qty' => $after]);

                $this->movement(
                    $branchId,
                    $branchProduct->product_id,
                    $userId,
                    'sale',
                    $before,
                    -$line['quantity'],
                    $after,
                    (float) $branchProduct->average_cost,
                    'sale',
                    $sale->id,
                    'Penjualan ' . $sale->sale_number
                );
            }

            $sale->payments()->create([
                'method_payment_id' => $data['method_payment_id'],
                'amount' => $grandTotal,
                'reference_number' => $data['reference_number'] ?? null,
            ]);

            return $sale->load(['items', 'payments.methodPayment', 'cashier']);
        }, 3);
    }

    public function adjust(array $data, int $branchId, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $branchId, $userId) {
            $existing = StockAdjustment::where('idempotency_key', $data['idempotency_key'])
                ->where('branch_store_id', $branchId)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing->load('items');
            }

            $items = collect($data['items'])
                ->filter(function ($item) {
                    return (float) $item['quantity_change'] !== 0.0;
                })
                ->sortBy('product_id')
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Masukkan minimal satu perubahan stok.']);
            }

            $adjustment = StockAdjustment::create([
                'branch_store_id' => $branchId,
                'created_by' => $userId,
                'adjustment_number' => $this->number('ADJ', 'pos_stock_adjustments'),
                'reason' => $data['reason'],
                'idempotency_key' => $data['idempotency_key'],
            ]);

            foreach ($items as $item) {
                $branchProduct = $this->lockedBranchProduct($branchId, (int) $item['product_id']);
                $before = (float) $branchProduct->stock_qty;
                $change = (float) $item['quantity_change'];
                $after = $before + $change;
                if ($after < 0) {
                    throw ValidationException::withMessages(['items' => 'Penyesuaian akan membuat stok menjadi minus.']);
                }

                $adjustment->items()->create([
                    'product_id' => $branchProduct->product_id,
                    'quantity_change' => $change,
                ]);
                $branchProduct->update(['stock_qty' => $after]);

                $this->movement(
                    $branchId,
                    $branchProduct->product_id,
                    $userId,
                    'adjustment',
                    $before,
                    $change,
                    $after,
                    (float) $branchProduct->average_cost,
                    'adjustment',
                    $adjustment->id,
                    $data['reason']
                );
            }

            return $adjustment->load('items');
        }, 3);
    }

    public function voidSale(Sale $sale, string $reason, int $userId): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $userId) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($sale->status === 'void') {
                return $sale;
            }

            foreach ($sale->items()->orderBy('product_id')->get() as $item) {
                $branchProduct = $this->lockedBranchProduct($sale->branch_store_id, $item->product_id);
                $before = (float) $branchProduct->stock_qty;
                $change = (float) $item->quantity;
                $after = $before + $change;
                $branchProduct->update(['stock_qty' => $after]);

                $this->movement(
                    $sale->branch_store_id,
                    $item->product_id,
                    $userId,
                    'sale_void',
                    $before,
                    $change,
                    $after,
                    (float) $item->unit_cost,
                    'sale',
                    $sale->id,
                    'Void ' . $sale->sale_number . ': ' . $reason
                );
            }

            $sale->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);

            return $sale->fresh(['items', 'payments.methodPayment', 'cashier']);
        }, 3);
    }

    public function number(string $prefix, string $table): string
    {
        $date = now()->format('Ymd');
        $base = $prefix . '-' . $date . '-';
        $latest = DB::table($table)
            ->where($table === 'pos_sales' ? 'sale_number' : ($table === 'pos_purchases' ? 'purchase_number' : 'adjustment_number'), 'like', $base . '%')
            ->lockForUpdate()
            ->count();

        return $base . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
    }

    private function lockedBranchProduct(int $branchId, int $productId): BranchProduct
    {
        $branchProduct = BranchProduct::where('branch_store_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (!$branchProduct) {
            throw ValidationException::withMessages(['product' => 'Produk belum terdaftar pada cabang ini.']);
        }

        return $branchProduct;
    }

    private function movement(
        int $branchId,
        int $productId,
        int $userId,
        string $type,
        float $before,
        float $change,
        float $after,
        float $unitCost,
        string $referenceType,
        int $referenceId,
        ?string $notes
    ): void {
        InventoryMovement::create([
            'branch_store_id' => $branchId,
            'product_id' => $productId,
            'created_by' => $userId,
            'movement_type' => $type,
            'quantity_before' => $before,
            'quantity_change' => $change,
            'quantity_after' => $after,
            'unit_cost' => $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }
}
