@php $product = $item ? $item->product : null; @endphp
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Product Name</label><input class="form-control" name="name" value="{{ old('name', optional($product)->name) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">SKU</label><input class="form-control" name="sku" value="{{ old('sku', optional($product)->sku) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Barcode</label><input class="form-control" name="barcode" value="{{ old('barcode', optional($product)->barcode) }}"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Category</label><select class="form-control" name="category_id"><option value="">Tanpa kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" {{ (string) old('category_id', optional($product)->category_id) === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select></div>
    <div class="col-md-2 mb-3"><label class="form-label">Unit</label><input class="form-control" name="unit" value="{{ old('unit', optional($product)->unit ?: 'pcs') }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Selling Price</label><input type="number" min="0" step="0.01" class="form-control" name="selling_price" value="{{ old('selling_price', optional($item)->selling_price ?: 0) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Minimum Stock</label><input type="number" min="0" step="0.001" class="form-control" name="minimum_stock" value="{{ old('minimum_stock', optional($item)->minimum_stock ?: 0) }}"></div>
    <div class="col-md-9 mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description">{{ old('description', optional($product)->description) }}</textarea></div>
    <div class="col-md-3 mb-3"><label class="form-label">Status at this branch</label><select class="form-control" name="is_active" required><option value="1" {{ (string) old('is_active', $item ? (int) $item->is_active : 1) === '1' ? 'selected' : '' }}>Active</option><option value="0" {{ (string) old('is_active', $item ? (int) $item->is_active : 1) === '0' ? 'selected' : '' }}>Inactive</option></select></div>
</div>
