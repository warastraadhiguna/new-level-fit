@php
    $summaryUrl = route('revenue-report.index', array_merge(request()->except(['page', 'view', 'category', 'search']), ['view' => 'summary']));
    $detailUrl = route('revenue-report.index', array_merge(request()->except(['page', 'view']), ['view' => 'detail']));
@endphp

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div class="mb-2">
                        <h4 class="mb-1">Revenue Report</h4>
                        <p class="text-muted mb-0">Membership and PT revenue uses the registration creation date and full package value plus admin fees, less discounts.</p>
                    </div>
                    @if (! $includePos)
                        <span class="badge badge-light mb-2">POS is disabled for this branch</span>
                    @endif
                </div>

                <div class="btn-group mb-4" role="group" aria-label="Revenue report display">
                    <a href="{{ $summaryUrl }}" class="btn {{ $viewMode === 'summary' ? 'btn-primary' : 'btn-outline-primary' }}">Summary</a>
                    <a href="{{ $detailUrl }}" class="btn {{ $viewMode === 'detail' ? 'btn-primary' : 'btn-outline-primary' }}">Revenue Details</a>
                </div>

                <form method="GET" action="{{ route('revenue-report.index') }}">
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <div class="row align-items-end g-2">
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                        </div>

                        @if ($viewMode === 'detail')
                            <div class="col-md-2">
                                <label class="form-label">Revenue Type</label>
                                <select name="category" class="form-control">
                                    <option value="">All Revenue</option>
                                    <option value="membership" {{ $category === 'membership' ? 'selected' : '' }}>Membership</option>
                                    <option value="pt" {{ $category === 'pt' ? 'selected' : '' }}>PT</option>
                                    @if ($includePos)
                                        <option value="pos" {{ $category === 'pos' ? 'selected' : '' }}>POS</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search Details</label>
                                <input type="text" name="search" class="form-control" value="{{ $search }}"
                                    placeholder="Member, package, reference, or staff">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Show</label>
                                <select name="per_page" class="form-control">
                                    @foreach ([10, 20, 50, 100] as $option)
                                        <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-auto"><button type="submit" class="btn btn-info">Show</button></div>
                        @if ($viewMode === 'detail' && Auth::user()->isOwner())
                            <div class="col-auto"><button type="submit" name="excel" value="1" class="btn btn-outline-info">Download Excel</button></div>
                        @endif
                    </div>
                </form>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-3">{{ $errors->first() }}</div>
                @endif
                <div class="border-top mt-4"></div>
            </div>
        </div>
    </div>
</div>

@if ($viewMode === 'summary')
    <div class="row">
        <div class="col-md-4 col-xl-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body py-3">
                    <div class="text-white-50">Total Revenue</div>
                    <h4 class="text-white mb-1">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</h4>
                    <small>{{ number_format($transactionCount, 0, ',', '.') }} payment transactions</small>
                </div>
            </div>
        </div>
        @foreach ($summary as $item)
            <div class="col-md-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted">{{ $item->category }}</div>
                        <h5 class="mb-1">Rp. {{ number_format($item->total_amount, 0, ',', '.') }}</h5>
                        <small>{{ number_format($item->transaction_count, 0, ',', '.') }} transactions</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mt-3">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Revenue Summary by Type</h5>
                            <small class="text-muted">Use Revenue Details to inspect each payment transaction.</small>
                        </div>
                        <a href="{{ $detailUrl }}" class="btn btn-primary btn-sm">View Revenue Details</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0">
                            <thead>
                                <tr>
                                    <th>Revenue Type</th>
                                    <th>Transactions</th>
                                    <th>Average / Transaction</th>
                                    <th>Contribution</th>
                                    <th class="text-end">Revenue</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary as $item)
                                    @php
                                        $categoryKeys = ['Membership' => 'membership', 'PT' => 'pt', 'POS' => 'pos'];
                                        $categoryKey = $categoryKeys[$item->category] ?? null;
                                        $average = $item->transaction_count > 0 ? $item->total_amount / $item->transaction_count : 0;
                                        $contribution = $grandTotal > 0 ? ($item->total_amount / $grandTotal) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td><span class="badge badge-primary">{{ $item->category }}</span></td>
                                        <td>{{ number_format($item->transaction_count, 0, ',', '.') }}</td>
                                        <td>Rp. {{ number_format($average, 0, ',', '.') }}</td>
                                        <td>{{ number_format($contribution, 1, ',', '.') }}%</td>
                                        <td class="text-end"><strong>Rp. {{ number_format($item->total_amount, 0, ',', '.') }}</strong></td>
                                        <td>
                                            <a class="btn btn-primary btn-xs" href="{{ route('revenue-report.index', ['view' => 'detail', 'from_date' => $fromDate, 'to_date' => $toDate, 'category' => $categoryKey]) }}">View Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted py-4">No revenue data found for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Revenue Details</h5>
                            <small class="text-muted">
                                {{ $category ? 'Showing the selected revenue type.' : 'Showing all revenue types.' }}
                                Each row represents one payment received.
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Filtered Revenue</small>
                            <h5 class="mb-0">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</h5>
                            <small>{{ number_format($transactionCount, 0, ',', '.') }} transactions</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th class="text-start">Member / Customer</th>
                                    <th class="text-start">Package / Description</th>
                                    <th>Payment</th>
                                    <th>Staff</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $item)
                                    <tr>
                                        <td>{{ $transactions->firstItem() + $loop->index }}</td>
                                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($item->transaction_at)->format('d M Y, H:i') }}</td>
                                        <td><span class="badge badge-primary">{{ $item->category }}</span></td>
                                        <td>{{ $item->reference_number }}</td>
                                        <td class="text-start"><strong>{{ $item->customer_name }}</strong><br><small class="text-muted">{{ $item->customer_code }}</small></td>
                                        <td class="text-start">{{ $item->item_name }}</td>
                                        <td>{{ $item->payment_method }}</td>
                                        <td>{{ $item->staff_name }}</td>
                                        <td class="text-end text-nowrap"><strong>Rp. {{ number_format($item->amount, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">No revenue details found for the selected filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                        <div class="text-muted mb-2">Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} data</div>
                        {{ $transactions->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
