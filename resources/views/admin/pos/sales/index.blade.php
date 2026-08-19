<div class="col-xl-12"><div class="card"><div class="card-body">
    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-3"><label class="form-label">From</label><input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}"></div>
        <div class="col-md-3"><label class="form-label">To</label><input type="date" class="form-control" name="date_to" value="{{ $dateTo }}"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
    <div class="row mb-3">
        <div class="col-md-3"><div class="alert alert-primary"><small>Completed Revenue</small><h4>Rp. {{ number_format($sales->where('status', 'completed')->sum('grand_total'), 0, ',', '.') }}</h4></div></div>
        <div class="col-md-3"><div class="alert alert-success"><small>Gross Profit</small><h4>Rp. {{ number_format($grossProfit, 0, ',', '.') }}</h4></div></div>
        <div class="col-md-3"><div class="alert alert-info"><small>Transactions</small><h4>{{ $sales->where('status', 'completed')->count() }}</h4></div></div>
        <div class="col-md-3"><div class="alert alert-warning"><small>Void</small><h4>{{ $sales->where('status', 'void')->count() }}</h4></div></div>
    </div>
    <div class="table-responsive"><table class="table" id="myTable" data-order-direction="desc"><thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
    @foreach ($sales as $sale)<tr><td>{{ $sale->sale_number }}</td><td data-order="{{ $sale->created_at->timestamp }}">{{ $sale->created_at->format('d M Y H:i') }}</td><td>{{ $sale->customer_name ?: '-' }}</td><td>{{ optional($sale->cashier)->full_name }}</td><td>{{ optional(optional($sale->payments->first())->methodPayment)->name }}</td><td>Rp. {{ number_format($sale->grand_total, 0, ',', '.') }}</td><td><span class="badge {{ $sale->status === 'completed' ? 'badge-success' : 'badge-danger' }}">{{ strtoupper($sale->status) }}</span></td><td><a href="{{ route('pos-sales.show', $sale->id) }}" class="btn btn-primary btn-xs">Detail</a></td></tr>@endforeach
    </tbody></table></div>
</div></div></div>
