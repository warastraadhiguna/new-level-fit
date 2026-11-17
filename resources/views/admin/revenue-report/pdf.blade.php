<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Revenue Report - {{ $viewMode === 'summary' ? 'Summary' : 'Details' }}</title>
    <style>
        @page { margin: 28px 28px 42px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        .header { text-align: center; border-bottom: 2px solid #4940aa; padding-bottom: 12px; }
        h1 { font-size: 18px; margin: 0 0 5px; }
        h2 { font-size: 13px; margin: 0 0 4px; }
        .filters { margin: 12px 0; width: 100%; }
        .filters td { padding: 3px 0; vertical-align: top; }
        .label { width: 105px; font-weight: bold; }
        .totals { margin: 12px 0; padding: 9px; background: #eeedf8; }
        .report { border-collapse: collapse; width: 100%; table-layout: fixed; }
        .report th, .report td { border: 1px solid #ccc; padding: 6px 5px; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        .report th { background: #eeedf8; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .number { text-align: right; }
        .center { text-align: center; }
        .muted { color: #666; }
        .total-row { font-weight: bold; background: #eeedf8; }
        .footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 8px; color: #666; text-align: right; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">Revenue Report | Page <span class="page-number"></span></div>
    <div class="header">
        <h1>{{ $branchName }}</h1>
        <h2>Revenue Report - {{ $viewMode === 'summary' ? 'Summary' : 'Details' }}</h2>
        <span>Revenue is recorded according to the date payment was received.</span>
    </div>
    @php
        $categoryNames = ['membership' => 'Membership', 'one_day' => 'One Day Visit', 'pt' => 'PT', 'lgt' => 'LGT', 'pos' => 'POS'];
    @endphp
    <table class="filters">
        <tr><td class="label">Period</td><td>: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</td></tr>
        <tr><td class="label">Payment Method</td><td>: {{ $paymentMethodName }}</td></tr>
        @if ($viewMode === 'detail')
            <tr><td class="label">Revenue Type</td><td>: {{ $categoryNames[$category] ?? 'All Revenue' }}</td></tr>
            <tr><td class="label">Search Details</td><td>: {{ filled($search) ? $search : 'All' }}</td></tr>
        @endif
        <tr><td class="label">Generated At</td><td>: {{ $generatedAt->format('d M Y, H:i') }}</td></tr>
    </table>
    <div class="totals">
        <strong>Total Revenue: Rp. {{ number_format($grandTotal, 0, ',', '.') }}</strong>
        &nbsp; | &nbsp; {{ number_format($transactionCount, 0, ',', '.') }} payment transactions
    </div>
    @if ($viewMode === 'summary')
        <table class="report">
            <thead><tr>
                <th>Revenue Type</th><th>Transactions</th><th>Average / Transaction</th><th>Contribution</th><th>Revenue</th>
            </tr></thead>
            <tbody>
                @forelse ($summary as $item)
                    <tr>
                        <td>{{ $item->category }}</td>
                        <td class="center">{{ number_format($item->transaction_count, 0, ',', '.') }}</td>
                        <td class="number">Rp. {{ number_format($item->transaction_count > 0 ? $item->total_amount / $item->transaction_count : 0, 0, ',', '.') }}</td>
                        <td class="number">{{ number_format($grandTotal > 0 ? $item->total_amount / $grandTotal * 100 : 0, 1, ',', '.') }}%</td>
                        <td class="number">Rp. {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="center">No revenue data found for this period.</td></tr>
                @endforelse
                <tr class="total-row"><td>Total</td><td class="center">{{ number_format($transactionCount, 0, ',', '.') }}</td><td colspan="2"></td><td class="number">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
    @else
        <table class="report">
            <thead><tr>
                <th style="width: 3%">No</th><th style="width: 11%">Date</th><th style="width: 8%">Type</th><th style="width: 9%">Reference</th><th style="width: 17%">Member / Customer</th><th style="width: 19%">Package / Description</th><th style="width: 9%">Payment</th><th style="width: 11%">Staff</th><th style="width: 13%">Amount</th>
            </tr></thead>
            <tbody>
                @forelse ($transactions as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->transaction_at)->format('d M Y, H:i') }}</td>
                        <td>{{ $item->category }}</td>
                        <td>{{ $item->reference_number }}</td>
                        <td><strong>{{ $item->customer_name }}</strong><br><span class="muted">{{ $item->customer_code }}</span></td>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->payment_method }}</td>
                        <td>{{ $item->staff_name }}</td>
                        <td class="number">Rp. {{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="center">No revenue details found for the selected filter.</td></tr>
                @endforelse
                <tr class="total-row"><td colspan="8">Total</td><td class="number">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
    @endif
</body>
</html>
