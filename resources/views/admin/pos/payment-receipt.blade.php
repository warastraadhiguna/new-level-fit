<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $receiptNumber }}</title>
    <style>
        @page { size: 58mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body {
            width: 100%; margin: 0; padding: 0; color: #000; background: #fff;
            font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.35;
        }
        .receipt {
            width: 40mm; max-width: 40mm; margin: 0 5mm 0 5mm;
            padding: 2mm 0 4mm; overflow: hidden;
        }
        .center { text-align: center; }
        .branch-name { margin: 0 0 1mm; font-size: 14px; font-weight: 700; }
        .receipt-title { margin-top: 1mm; font-weight: 700; }
        .separator { margin: 2mm 0; border-top: 1px dashed #000; }
        table {
            width: 100%; max-width: 100%; table-layout: fixed;
            border-collapse: collapse; font-size: 9px;
        }
        td { padding: 0.3mm 0; vertical-align: top; }
        .meta td:first-child { width: 15mm; padding-left: 2mm; }
        .meta td:last-child { overflow-wrap: anywhere; }
        .summary td:first-child { padding-left: 2mm; }
        .amount { width: 58%; text-align: right; white-space: nowrap; }
        .grand-total td { padding-top: 1mm; font-size: 11px; font-weight: 700; }
        .actions {
            display: flex; width: 40mm; max-width: 100%; margin: 0 5mm;
            gap: 8px; padding: 12px; background: #f2f2f2;
        }
        .actions button, .actions a {
            flex: 1; padding: 9px 6px; color: #fff; background: #5146bd;
            border: 0; border-radius: 4px; font-size: 12px; text-align: center;
            text-decoration: none; cursor: pointer;
        }
        .actions a { color: #222; background: #ddd; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    @php
        $hasReceivedAmount = $payment->received_amount !== null;
        $receivedAmount = $hasReceivedAmount ? (int) $payment->received_amount : null;
    @endphp
    <main class="receipt">
        <header class="center">
            <h1 class="branch-name">{{ optional($branchStore)->name ?: config('app.name') }}</h1>
            @if (optional($branchStore)->address)<div>{{ $branchStore->address }}</div>@endif
            @if (optional($branchStore)->phone)<div>Telp. {{ $branchStore->phone }}</div>@endif
            <div class="receipt-title">{{ $receiptTitle }}</div>
        </header>

        <div class="separator"></div>
        <table class="meta">
            <tr><td>No.</td><td>: {{ $receiptNumber }}</td></tr>
            <tr><td>Tanggal</td><td>: {{ $payment->created_at->format('d-m-Y H:i') }}</td></tr>
            <tr><td>Kasir</td><td>: {{ optional($payment->user)->full_name ?: '-' }}</td></tr>
            <tr><td>Member</td><td>: {{ $customerName }}</td></tr>
            @if ($customerCode)<tr><td>Kode</td><td>: {{ $customerCode }}</td></tr>@endif
            <tr><td>Paket</td><td>: {{ $packageName ?: '-' }}</td></tr>
            <tr><td>Metode</td><td>: {{ optional($payment->methodPayment)->name ?: '-' }}</td></tr>
            @if ($payment->note)<tr><td>Catatan</td><td>: {{ $payment->note }}</td></tr>@endif
        </table>

        <div class="separator"></div>
        <table class="summary">
            <tr><td>Harga Paket</td><td class="amount">Rp. {{ number_format($packagePrice, 0, ',', '.') }}</td></tr>
            @if ($adminPrice > 0)<tr><td>Biaya Admin</td><td class="amount">Rp. {{ number_format($adminPrice, 0, ',', '.') }}</td></tr>@endif
            @if ($discountAmount > 0)<tr><td>Diskon</td><td class="amount">- Rp. {{ number_format($discountAmount, 0, ',', '.') }}</td></tr>@endif
            <tr><td>Total Tagihan</td><td class="amount">Rp. {{ number_format($totalPayable, 0, ',', '.') }}</td></tr>
            <tr class="grand-total"><td>Pembayaran</td><td class="amount">Rp. {{ number_format($payment->value, 0, ',', '.') }}</td></tr>
            <tr><td>Uang Bayar</td><td class="amount">{{ $hasReceivedAmount ? 'Rp. ' . number_format($receivedAmount, 0, ',', '.') : '-' }}</td></tr>
            <tr><td>Kembali</td><td class="amount">{{ $hasReceivedAmount ? 'Rp. ' . number_format(max(0, $receivedAmount - (int) $payment->value), 0, ',', '.') : '-' }}</td></tr>
            <tr><td>Total Terbayar</td><td class="amount">Rp. {{ number_format($paidToDate, 0, ',', '.') }}</td></tr>
            <tr><td>Sisa</td><td class="amount">Rp. {{ number_format(max(0, $totalPayable - $paidToDate), 0, ',', '.') }}</td></tr>
        </table>

        <div class="separator"></div>
        <footer class="center">Terima kasih</footer>
    </main>

    <div class="actions no-print">
        <a href="{{ $backUrl }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak Struk</button>
    </div>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
</body>
</html>
