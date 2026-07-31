<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\BranchProduct;
use App\Models\Pos\InventoryMovement;
use App\Services\PosInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        return view('admin.layouts.wrapper', [
            'title' => 'Stock Card & Adjustment',
            'content' => 'admin.pos.stock.index',
            'products' => BranchProduct::with('product')
                ->where('branch_store_id', Auth::user()->branch_store_id)
                ->where('is_active', true)
                ->get()
                ->sortBy('product.name'),
            'movements' => InventoryMovement::with(['product', 'creator'])
                ->where('branch_store_id', Auth::user()->branch_store_id)
                ->latest('id')
                ->limit(200)
                ->get(),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, PosInventoryService $inventory)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:pos_products,id'],
            'items.*.quantity_change' => ['required', 'numeric', 'not_in:0'],
        ]);
        $inventory->adjust($data, Auth::user()->branch_store_id, Auth::id());

        return redirect()->route('pos-stock.index')->with('success', 'Penyesuaian stok berhasil dicatat.');
    }
}
