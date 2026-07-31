<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\BranchProduct;
use App\Models\Pos\Product;
use App\Models\Pos\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $branchId = Auth::user()->branch_store_id;

        return view('admin.layouts.wrapper', [
            'title' => 'Product & Stock Management',
            'content' => 'admin.pos.products.index',
            'products' => BranchProduct::with('product.category')
                ->where('branch_store_id', $branchId)
                ->orderBy('id', 'desc')
                ->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'availableProducts' => Product::whereDoesntHave('branchProducts', function ($query) use ($branchId) {
                $query->where('branch_store_id', $branchId);
            })->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $branchId = (int) Auth::user()->branch_store_id;

        DB::transaction(function () use ($data, $branchId) {
            $product = Product::create([
                'category_id' => $data['category_id'] ?? null,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'],
                'unit' => $data['unit'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);

            BranchProduct::create([
                'branch_store_id' => $branchId,
                'product_id' => $product->id,
                'selling_price' => $data['selling_price'],
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_active' => $data['is_active'],
            ]);
        });

        return redirect()->route('pos-products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $branchProduct = BranchProduct::where('branch_store_id', Auth::user()->branch_store_id)
            ->findOrFail($id);
        $data = $this->validateProduct($request, $branchProduct->product_id);

        DB::transaction(function () use ($branchProduct, $data) {
            $branchProduct->product->update([
                'category_id' => $data['category_id'] ?? null,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'],
                'unit' => $data['unit'],
                'description' => $data['description'] ?? null,
            ]);
            $branchProduct->update([
                'selling_price' => $data['selling_price'],
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_active' => $data['is_active'],
            ]);
        });

        return redirect()->route('pos-products.index')->with('success', 'Produk berhasil diubah.');
    }

    public function attach(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:pos_products,id'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
        ]);

        BranchProduct::firstOrCreate(
            [
                'branch_store_id' => Auth::user()->branch_store_id,
                'product_id' => $data['product_id'],
            ],
            [
                'selling_price' => $data['selling_price'],
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_active' => true,
            ]
        );

        return redirect()->route('pos-products.index')->with('success', 'Produk ditambahkan ke cabang ini.');
    }

    public function destroy($id)
    {
        $branchProduct = BranchProduct::where('branch_store_id', Auth::user()->branch_store_id)
            ->findOrFail($id);

        if ((float) $branchProduct->stock_qty > 0) {
            return redirect()->back()->with('errorr', 'Produk masih memiliki stok. Nonaktifkan produk melalui Edit.');
        }

        $branchProduct->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Produk dinonaktifkan pada cabang ini.');
    }

    private function validateProduct(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:pos_product_categories,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('pos_products', 'sku')->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('pos_products', 'barcode')->ignore($productId)],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
