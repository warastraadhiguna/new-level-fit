<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->sale_number }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.35;
        }

        .receipt {
            width: 40mm;
            max-width: 40mm;
            margin: 0 5mm 0 5mm;
            padding: 2mm 0 4mm;
            overflow: hidden;
        }

        .center {
            text-align: center;
        }

        .branch-name {
            margin: 0 0 1mm;
            font-size: 14px;
            font-weight: 700;
        }

        .separator {
            margin: 2mm 0;
            border-top: 1px dashed #000;
        }

        .meta,
        .totals,
        .payment {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 9px;
        }

        .meta td,
        .totals td,
        .payment td {
            padding: 0.3mm 0;
            vertical-align: top;
        }

        .meta td:first-child {
            width: 15mm;
            padding-left: 2mm;
        }

        .meta td:last-child {
            overflow-wrap: anywhere;
        }

        .amount {
            width: 58%;
            text-align: right;
            white-space: nowrap;
        }

        .item {
            margin-bottom: 1.5mm;
        }

        .item-name {
            padding-left: 2mm;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .item-calculation {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            column-gap: 1mm;
            font-size: 9px;
        }

        .item-calculation span:first-child {
            padding-left: 2mm;
            overflow-wrap: anywhere;
        }

        .item-calculation span:last-child {
            white-space: nowrap;
            text-align: right;
        }

        .grand-total td {
            padding-top: 1mm;
            font-size: 11px;
            font-weight: 700;
        }

        .totals td:first-child,
        .payment td:first-child {
            padding-left: 2mm;
        }

        .status-void {
            margin-top: 2mm;
            padding: 1.5mm;
            border: 1px solid #000;
            text-align: center;
            font-weight: 700;
        }

        .actions {
            display: flex;
            width: 40mm;
            max-width: 100%;
            margin: 0 5mm 0 5mm;
            gap: 8px;
            padding: 12px;
            background: #f2f2f2;
        }

        .actions button,
        .actions a {
            flex: 1;
            padding: 9px 6px;
            color: #fff;
            background: #5146bd;
            border: 0;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .actions a {
            color: #222;
            background: #ddd;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <main class="receipt">
        <header class="center">
            <h1 class="branch-name">{{ optional($sale->branchStore)->name ?: config('app.name') }}</h1>
            @if (optional($sale->branchStore)->address)
                <div>{{ $sale->branchStore->address }}</div>
            @endif
            @if (optional($sale->branchStore)->phone)
                <div>Telp. {{ $sale->branchStore->phone }}</div>
            @endif
        </header>

        <div class="separator"></div>

        <table class="meta">
            <tr><td>No.</td><td>: {{ $sale->sale_number }}</td></tr>
            <tr><td>Tanggal</td><td>: {{ $sale->created_at->format('d-m-Y H:i') }}</td></tr>
            <tr><td>Kasir</td><td>: {{ optional($sale->cashier)->full_name ?: '-' }}</td></tr>
            <tr><td>Pelanggan</td><td>: {{ $sale->customer_name ?: '-' }}</td></tr>
        </table>

        <div class="separator"></div>

        @foreach ($sale->items as $item)
            @php
                $quantity = rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',');
            @endphp
            <div class="item">
                <div class="item-name">{{ $item->product_name }}</div>
                <div class="item-calculation">
                    <span>{{ $quantity }} x Rp. {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    <span>Rp. {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach

        <div class="separator"></div>

        <table class="totals">
            <tr><td>Subtotal</td><td class="amount">Rp. {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
            @if ((float) $sale->discount_amount > 0)
                <tr><td>Diskon</td><td class="amount">- Rp. {{ number_format($sale->discount_amount, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="grand-total"><td>Total</td><td class="amount">Rp. {{ number_format($sale->grand_total, 0, ',', '.') }}</td></tr>
            <tr><td>Bayar</td><td class="amount">Rp. {{ number_format($sale->paid_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Kembali</td><td class="amount">Rp. {{ number_format($sale->change_amount, 0, ',', '.') }}</td></tr>
        </table>

        @if ($sale->payments->isNotEmpty())
            <div class="separator"></div>
            <table class="payment">
                @foreach ($sale->payments as $payment)
                    <tr>
                        <td>{{ optional($payment->methodPayment)->name ?: 'Pembayaran' }}</td>
                        <td class="amount">Rp. {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    </tr>
                    @if ($payment->reference_number)
                        <tr><td colspan="2">Ref: {{ $payment->reference_number }}</td></tr>
                    @endif
                @endforeach
            </table>
        @endif

        @if ($sale->status === 'void')
            <div class="status-void">TRANSAKSI DIBATALKAN</div>
        @endif

        <div class="separator"></div>
        <footer class="center">Terima kasih</footer>
    </main>

    <div class="actions no-print">
        <a href="{{ route('pos-sales.show', $sale->id) }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak Struk</button>
    </div>

    @if (request()->boolean('autoprint'))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
