<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\MethodPayment;
use App\Models\Pos\BranchProduct;
use App\Models\Pos\Sale;
use App\Services\PosInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $products = BranchProduct::with('product.category')
            ->where('branch_store_id', Auth::user()->branch_store_id)
            ->where('is_active', true)
            ->where('stock_qty', '>', 0)
            ->get()
            ->filter(function ($item) {
                return $item->product && $item->product->is_active;
            })
            ->values();

        return view('admin.layouts.wrapper', [
            'title' => 'Point of Sale',
            'content' => 'admin.pos.cashier.index',
            'products' => $products,
            'productPayload' => $products->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode,
                    'unit' => $item->product->unit,
                    'stock' => (float) $item->stock_qty,
                    'price' => (float) $item->selling_price,
                ];
            })->values(),
            'paymentMethods' => MethodPayment::orderBy('name')->get(),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function checkout(Request $request, PosInventoryService $inventory)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:150'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'method_payment_id' => ['required', 'exists:method_payments,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:pos_products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $sale = $inventory->checkout($data, Auth::user()->branch_store_id, Auth::id());

        return redirect()->route('pos-sales.show', $sale->id)->with('success', 'Transaksi berhasil.');
    }

    public function sales()
    {
        $query = Sale::with(['cashier', 'payments.methodPayment', 'items'])
            ->where('branch_store_id', Auth::user()->branch_store_id);

        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        $sales = $query->latest('id')->get();
        $completed = $sales->where('status', 'completed');
        $costOfGoods = $completed->sum(function ($sale) {
            return $sale->items->sum(function ($item) {
                return (float) $item->quantity * (float) $item->unit_cost;
            });
        });

        return view('admin.layouts.wrapper', [
            'title' => 'POS Sales Report',
            'content' => 'admin.pos.sales.index',
            'sales' => $sales,
            'grossProfit' => (float) $completed->sum('grand_total') - $costOfGoods,
        ]);
    }

    public function showSale($id)
    {
        return view('admin.layouts.wrapper', [
            'title' => 'POS Receipt',
            'content' => 'admin.pos.sales.show',
            'sale' => Sale::with(['items', 'payments.methodPayment', 'cashier'])
                ->where('branch_store_id', Auth::user()->branch_store_id)
                ->findOrFail($id),
        ]);
    }

    public function voidSale(Request $request, $id, PosInventoryService $inventory)
    {
        $data = $request->validate(['void_reason' => ['required', 'string', 'max:1000']]);
        $sale = Sale::where('branch_store_id', Auth::user()->branch_store_id)->findOrFail($id);
        $inventory->voidSale($sale, $data['void_reason'], Auth::id());

        return redirect()->route('pos-sales.show', $sale->id)->with('success', 'Transaksi dibatalkan dan stok dikembalikan.');
    }
}
