<div class="col-xl-12"><div class="page-title"><a href="{{ route('pos-purchases.create') }}" class="btn btn-primary">+ New Purchase</a></div></div>
<div class="col-xl-12"><div class="card"><div class="card-body"><div class="table-responsive">
    <table class="table" id="myTable">
        <thead><tr><th>Number</th><th>Date</th><th>Supplier</th><th>Invoice</th><th>Total</th><th>Status</th><th>Created By</th><th>Action</th></tr></thead>
        <tbody>@foreach ($purchases as $purchase)
            <tr>
                <td>{{ $purchase->purchase_number }}</td><td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                <td>{{ optional($purchase->supplier)->name ?: '-' }}</td><td>{{ $purchase->supplier_invoice_number ?: '-' }}</td>
                <td>Rp. {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                <td><span class="badge {{ $purchase->status === 'received' ? 'badge-success' : ($purchase->status === 'draft' ? 'badge-warning' : 'badge-secondary') }}">{{ strtoupper($purchase->status) }}</span></td>
                <td>{{ optional($purchase->creator)->full_name }}</td>
                <td><a class="btn btn-primary btn-xs" href="{{ route('pos-purchases.show', $purchase->id) }}">Detail</a></td>
            </tr>
        @endforeach</tbody>
    </table>
</div></div></div></div>
