<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\BranchProduct;
use App\Models\Pos\Purchase;
use App\Models\Pos\Supplier;
use App\Services\PosInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('admin.layouts.wrapper', [
            'title' => 'Product Purchases',
            'content' => 'admin.pos.purchases.index',
            'purchases' => Purchase::with(['supplier', 'creator'])
                ->where('branch_store_id', Auth::user()->branch_store_id)
                ->latest('id')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('admin.layouts.wrapper', [
            'title' => 'Create Product Purchase',
            'content' => 'admin.pos.purchases.create',
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => BranchProduct::with('product')
                ->where('branch_store_id', Auth::user()->branch_store_id)
                ->where('is_active', true)
                ->get()
                ->sortBy('product.name'),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, PosInventoryService $inventory)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:pos_suppliers,id'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:pos_products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $branchId = (int) Auth::user()->branch_store_id;
        $purchase = DB::transaction(function () use ($data, $branchId, $inventory) {
            $existing = Purchase::where('idempotency_key', $data['idempotency_key'])
                ->where('branch_store_id', $branchId)
                ->first();
            if ($existing) {
                return $existing;
            }

            $allowedIds = BranchProduct::where('branch_store_id', $branchId)
                ->whereIn('product_id', collect($data['items'])->pluck('product_id'))
                ->pluck('product_id')
                ->map(function ($id) { return (int) $id; });
            if ($allowedIds->count() !== count($data['items'])) {
                abort(422, 'Salah satu produk tidak terdaftar pada cabang ini.');
            }

            $total = collect($data['items'])->sum(function ($item) {
                return (float) $item['quantity'] * (float) $item['unit_cost'];
            });
            $purchase = Purchase::create([
                'branch_store_id' => $branchId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'created_by' => Auth::id(),
                'purchase_number' => $inventory->number('PO', 'pos_purchases'),
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => 'draft',
                'total_amount' => $total,
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
            ]);
            foreach ($data['items'] as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => (float) $item['quantity'] * (float) $item['unit_cost'],
                ]);
            }

            return $purchase;
        });

        return redirect()->route('pos-purchases.show', $purchase->id)->with('success', 'Draft pembelian berhasil disimpan.');
    }

    public function show($id)
    {
        return view('admin.layouts.wrapper', [
            'title' => 'Purchase Detail',
            'content' => 'admin.pos.purchases.show',
            'purchase' => Purchase::with(['items.product', 'supplier', 'creator'])
                ->where('branch_store_id', Auth::user()->branch_store_id)
                ->findOrFail($id),
        ]);
    }

    public function receive($id, PosInventoryService $inventory)
    {
        $purchase = Purchase::where('branch_store_id', Auth::user()->branch_store_id)->findOrFail($id);
        $inventory->receivePurchase($purchase, Auth::id());

        return redirect()->route('pos-purchases.show', $purchase->id)
            ->with('success', 'Barang diterima. Stok dan HPP rata-rata sudah diperbarui.');
    }

    public function destroy($id)
    {
        $purchase = Purchase::where('branch_store_id', Auth::user()->branch_store_id)->findOrFail($id);
        if ($purchase->status !== 'draft') {
            return redirect()->back()->with('errorr', 'Pembelian yang sudah diterima tidak dapat dibatalkan.');
        }
        $purchase->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Draft pembelian dibatalkan.');
    }
}
