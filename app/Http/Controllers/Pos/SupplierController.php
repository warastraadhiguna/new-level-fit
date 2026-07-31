<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('admin.layouts.wrapper', [
            'title' => 'Supplier POS',
            'content' => 'admin.pos.suppliers.index',
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Supplier::create($data);

        return redirect()->route('pos-suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        Supplier::findOrFail($id)->update($this->validated($request));

        return redirect()->route('pos-suppliers.index')->with('success', 'Supplier berhasil diubah.');
    }

    public function destroy($id)
    {
        Supplier::findOrFail($id)->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Supplier dinonaktifkan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
