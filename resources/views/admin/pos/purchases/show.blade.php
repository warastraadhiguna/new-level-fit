<div class="col-xl-12"><div class="card">
    <div class="card-header"><div><h4>{{ $purchase->purchase_number }}</h4><span class="badge {{ $purchase->status === 'received' ? 'badge-success' : ($purchase->status === 'draft' ? 'badge-warning' : 'badge-secondary') }}">{{ strtoupper($purchase->status) }}</span></div><a href="{{ route('pos-purchases.index') }}" class="btn btn-light">Back</a></div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3"><small>Purchase Date</small><div>{{ $purchase->purchase_date->format('d M Y') }}</div></div>
            <div class="col-md-3"><small>Supplier</small><div>{{ optional($purchase->supplier)->name ?: '-' }}</div></div>
            <div class="col-md-3"><small>Invoice</small><div>{{ $purchase->supplier_invoice_number ?: '-' }}</div></div>
            <div class="col-md-3"><small>Received At</small><div>{{ $purchase->received_at ? $purchase->received_at->format('d M Y H:i') : '-' }}</div></div>
        </div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Unit Cost</th><th>Subtotal</th></tr></thead><tbody>
            @foreach ($purchase->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->quantity }} {{ $item->product->unit }}</td><td>Rp. {{ number_format($item->unit_cost, 0, ',', '.') }}</td><td>Rp. {{ number_format($item->subtotal, 0, ',', '.') }}</td></tr>@endforeach
        </tbody><tfoot><tr><th colspan="3" class="text-end">Total</th><th>Rp. {{ number_format($purchase->total_amount, 0, ',', '.') }}</th></tr></tfoot></table></div>
        @if ($purchase->notes)<div class="mt-3"><strong>Notes:</strong> {{ $purchase->notes }}</div>@endif
    </div>
    @if ($purchase->status === 'draft')
        <div class="card-footer d-flex justify-content-end gap-2">
            <form action="{{ route('pos-purchases.destroy', $purchase->id) }}" method="POST" onsubmit="return confirm('Batalkan draft pembelian ini?')">@csrf @method('DELETE')<button class="btn btn-danger light">Cancel Draft</button></form>
            <form action="{{ route('pos-purchases.receive', $purchase->id) }}" method="POST" onsubmit="this.querySelector('button').disabled=true; return confirm('Terima barang dan update stok/HPP? Data pembelian tidak dapat diedit setelah diterima.')">@csrf<button class="btn btn-success">Receive Goods</button></form>
        </div>
    @endif
</div></div>
