<div class="col-xl-12">
    <div class="page-title flex-wrap">
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProduct">+ New Product</button>
            @if ($availableProducts->isNotEmpty())
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#attachProduct">+ Existing Product</button>
            @endif
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageCategory">Category</button>
        </div>
        <div class="text-muted">Stok dan HPP hanya berubah melalui penerimaan pembelian atau penyesuaian stok.</div>
    </div>
</div>

@if ($availableProducts->isNotEmpty())
<div class="modal fade" id="attachProduct" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content pos-submit-once" action="{{ route('pos-products.attach') }}" method="POST">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Add Existing Product to This Branch</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Product</label><select class="form-control" name="product_id" required><option value="">-- Choose --</option>@foreach ($availableProducts as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Selling Price</label><input type="number" min="0" step="0.01" class="form-control" name="selling_price" required></div>
                <div class="mb-3"><label class="form-label">Minimum Stock</label><input type="number" min="0" step="0.001" value="0" class="form-control" name="minimum_stock"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" type="submit">Add to Branch</button></div>
        </form>
    </div>
</div>
@endif

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="myTable">
                    <thead>
                        <tr>
                            <th>SKU / Barcode</th>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Average Cost</th>
                            <th>Selling Price</th>
                            <th>Stock Value</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr class="{{ (float) $item->stock_qty <= (float) $item->minimum_stock ? 'table-warning' : '' }}">
                                <td>{{ $item->product->sku }}<br><small>{{ $item->product->barcode ?: '-' }}</small></td>
                                <td><strong>{{ $item->product->name }}</strong><br><small>{{ optional($item->product->category)->name ?: 'Tanpa kategori' }}</small></td>
                                <td>{{ rtrim(rtrim(number_format($item->stock_qty, 3, ',', '.'), '0'), ',') }} {{ $item->product->unit }}</td>
                                <td>Rp. {{ number_format($item->average_cost, 0, ',', '.') }}</td>
                                <td>Rp. {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                <td>Rp. {{ number_format((float) $item->stock_qty * (float) $item->average_cost, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $item->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td><button class="btn btn-warning btn-xs" data-bs-toggle="modal" data-bs-target="#editProduct{{ $item->id }}">Edit</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addProduct" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content pos-submit-once" action="{{ route('pos-products.store') }}" method="POST">
            @csrf
            <div class="modal-header"><h5 class="modal-title">New Product</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('admin.pos.products.partials.form', ['item' => null])</div>
            <div class="modal-footer"><button class="btn btn-primary" type="submit">Save</button><button class="btn btn-danger light" type="button" data-bs-dismiss="modal">Close</button></div>
        </form>
    </div>
</div>

@foreach ($products as $item)
    <div class="modal fade" id="editProduct{{ $item->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content pos-submit-once" action="{{ route('pos-products.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit {{ $item->product->name }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">@include('admin.pos.products.partials.form', ['item' => $item])</div>
                <div class="modal-footer"><button class="btn btn-primary" type="submit">Update</button><button class="btn btn-danger light" type="button" data-bs-dismiss="modal">Close</button></div>
            </form>
        </div>
    </div>
@endforeach

<div class="modal fade" id="manageCategory" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Product Categories</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form action="{{ route('pos-product-categories.store') }}" method="POST" class="row g-2 mb-4 pos-submit-once">
                    @csrf
                    <div class="col-md-5"><input class="form-control" name="name" placeholder="Category name" required></div>
                    <div class="col-md-5"><input class="form-control" name="description" placeholder="Description"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                </form>
                <table class="table">
                    @foreach ($categories as $category)
                        <tr><td>{{ $category->name }}</td><td>{{ $category->description }}</td><td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pos-submit-once').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('[type="submit"]');
            if (button) { button.disabled = true; button.innerHTML = 'Saving...'; }
        });
    });
});
</script>
