<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:pos_product_categories,name'],
            'description' => ['nullable', 'string'],
        ]);
        $data['is_active'] = true;
        ProductCategory::create($data);

        return redirect()->route('pos-products.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('pos_product_categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);
        $category->update($data);

        return redirect()->route('pos-products.index')->with('success', 'Kategori berhasil diubah.');
    }
}
