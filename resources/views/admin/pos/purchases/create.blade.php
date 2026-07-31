<div class="col-xl-12">
    <form class="card pos-submit-once" action="{{ route('pos-purchases.store') }}" method="POST" id="purchaseForm">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        <div class="card-header"><h4 class="card-title">New Purchase Draft</h4></div>
        <div class="card-body">
            <div class="alert alert-info">Menyimpan draft belum menambah stok. Stok dan HPP rata-rata baru berubah saat tombol <strong>Receive Goods</strong> ditekan pada detail pembelian.</div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Purchase Date</label><input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Supplier</label><select class="form-control" name="supplier_id"><option value="">Tanpa supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Supplier Invoice Number</label><input class="form-control" name="supplier_invoice_number"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes"></textarea></div>
            </div>
            <div class="table-responsive">
                <table class="table" id="purchaseItems"><thead><tr><th style="width:45%">Product</th><th>Quantity</th><th>Purchase Price / Unit</th><th>Subtotal</th><th></th></tr></thead><tbody></tbody></table>
            </div>
            <button type="button" class="btn btn-outline-primary" id="addPurchaseItem">+ Add Product</button>
            <h4 class="text-end mt-3">Total: <span id="purchaseTotal">Rp. 0</span></h4>
        </div>
        <div class="card-footer text-end"><a href="{{ route('pos-purchases.index') }}" class="btn btn-light">Back</a><button type="submit" class="btn btn-primary">Save Draft</button></div>
    </form>
</div>
<template id="purchaseRow">
    <tr>
        <td><select class="form-control product-input" required><option value="">-- Choose Product --</option>@foreach ($products as $item)<option value="{{ $item->product_id }}">{{ $item->product->name }} ({{ $item->product->sku }})</option>@endforeach</select></td>
        <td><input type="number" min="0.001" step="0.001" class="form-control qty-input" value="1" required></td>
        <td><input type="number" min="0" step="0.01" class="form-control cost-input" value="0" required></td>
        <td class="line-total">Rp. 0</td><td><button type="button" class="btn btn-danger btn-xs remove-line">×</button></td>
    </tr>
</template>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.querySelector('#purchaseItems tbody');
    const template = document.querySelector('#purchaseRow');
    function money(value) { return 'Rp. ' + Number(value).toLocaleString('id-ID'); }
    function reindex() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach(function (row, index) {
            row.querySelector('.product-input').name = `items[${index}][product_id]`;
            row.querySelector('.qty-input').name = `items[${index}][quantity]`;
            row.querySelector('.cost-input').name = `items[${index}][unit_cost]`;
            const subtotal = Number(row.querySelector('.qty-input').value || 0) * Number(row.querySelector('.cost-input').value || 0);
            row.querySelector('.line-total').textContent = money(subtotal);
            total += subtotal;
        });
        document.querySelector('#purchaseTotal').textContent = money(total);
    }
    function addRow() {
        tbody.appendChild(template.content.cloneNode(true));
        reindex();
    }
    document.querySelector('#addPurchaseItem').addEventListener('click', addRow);
    tbody.addEventListener('input', reindex);
    tbody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-line')) { event.target.closest('tr').remove(); reindex(); }
    });
    document.querySelector('#purchaseForm').addEventListener('submit', function () {
        const button = this.querySelector('[type=submit]'); button.disabled = true; button.textContent = 'Saving...';
    });
    addRow();
});
</script>
