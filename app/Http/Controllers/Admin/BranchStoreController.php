<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchStoreController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Secret Branch Store',
            'branchStores' => BranchStore::orderBy('name')->get(),
            'content' => 'admin.branch-store.index',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->resolveSlug($request->input('slug'), $request->input('name'));

        if ($request->hasFile('admin_logo')) {
            $data['admin_logo'] = $request->file('admin_logo')->store('assets/branch-store', 'public');
        }

        BranchStore::create($data);

        return redirect()->route('secret-branch-store.index')->with('message', 'Cabang berhasil ditambahkan');
    }

    public function update(Request $request, string $id)
    {
        $branchStore = BranchStore::findOrFail($id);
        $data = $this->validatedData($request, $branchStore->id);
        $data['slug'] = $this->resolveSlug($request->input('slug'), $request->input('name'), $branchStore->id);

        if ($request->hasFile('admin_logo')) {
            $this->deleteAdminLogo($branchStore->admin_logo);
            $data['admin_logo'] = $request->file('admin_logo')->store('assets/branch-store', 'public');
        }

        $branchStore->update($data);

        return redirect()->route('secret-branch-store.index')->with('message', 'Cabang berhasil diubah');
    }

    public function destroy(string $id)
    {
        $branchStore = BranchStore::findOrFail($id);

        if ($this->branchStoreIsInUse($branchStore->id)) {
            return redirect()->route('secret-branch-store.index')->with('errorr', 'Cabang gagal dihapus, masih dipakai data lain');
        }

        try {
            $this->deleteAdminLogo($branchStore->admin_logo);
            $branchStore->delete();

            return redirect()->route('secret-branch-store.index')->with('message', 'Cabang berhasil dihapus');
        } catch (\Throwable $th) {
            return redirect()->route('secret-branch-store.index')->with('errorr', 'Cabang gagal dihapus, kemungkinan masih dipakai data lain');
        }
    }

    private function branchStoreIsInUse(int $branchStoreId): bool
    {
        $tables = [
            'users',
            'members',
            'member_packages',
            'trainer_packages',
            'trainer_sessions',
            'personal_trainers',
            'class_sessions',
            'trainers',
            'pos_branch_products',
            'pos_purchases',
            'pos_sales',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (DB::table($table)->where('branch_store_id', $branchStoreId)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function validatedData(Request $request, ?int $branchStoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('branch_stores', 'slug')->ignore($branchStoreId)],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:45'],
            'is_payment_strict' => ['required', 'boolean'],
            'member_installment_enabled' => ['required', 'boolean'],
            'member_installment_grace_days' => ['required', 'integer', 'min:0', 'max:30'],
            'member_installment_cancel_days' => ['required', 'integer', 'min:1', 'max:365', 'gte:member_installment_grace_days'],
            'member_discount_enabled' => ['required', 'boolean'],
            'trainer_discount_enabled' => ['required', 'boolean'],
            'pos_inventory_enabled' => ['required', 'boolean'],
            'type' => ['required', Rule::in(['both', 'male', 'female'])],
            'admin_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,ico,webp', 'max:2048'],
        ]);
    }

    private function resolveSlug(?string $submittedSlug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($submittedSlug ?: $name);

        if ($baseSlug === '') {
            $baseSlug = 'branch-store';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            BranchStore::where('slug', $slug)
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function deleteAdminLogo(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'storage/', '/storage/'])) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
