<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Member</title>
</head>

<body>
    <table>
        <thead>
            <tr style="background-color: #f4b6e5; font-weight: bold;">
                <th>Nomor</th>
                <th>Nama Member / Nomor Member</th>
                <th>Nama Paket</th>
                <th>All Club / One Club</th>
                <th>Cabang</th>
                <th>Status</th>
                <th>Masa Aktif</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($memberRegistrations as $item)
                @php
                    $memberName = $item->member_name ?? $item->full_name ?? '-';
                    $memberCode = $item->member_code ?? '-';
                    $expiredDate = $item->expired_date ?? $item->max_end_date ?? null;
                    $isAllClub = in_array(
                        strtolower((string) ($item->is_all_club ?? 0)),
                        ['1', 'true', 'yes', 'all club', 'all_club'],
                        true
                    );

                    if ($exportType === 'expired') {
                        $membershipStatus = 'Expired';
                    } elseif ($exportType === 'pending') {
                        $membershipStatus = 'Pending';
                    } elseif (($item->leave_day_status ?? null) === 'Freeze') {
                        $membershipStatus = 'Freeze';
                    } elseif ($exportType === 'active') {
                        $membershipStatus = 'Running';
                    } else {
                        $rawStatus = strtolower((string) ($item->status ?? 'Running'));
                        $membershipStatus = $rawStatus === 'over'
                            ? 'Expired'
                            : ($rawStatus === 'not started' ? 'Pending' : 'Running');
                    }

                    $totalBill = (float) ($item->mr_package_price ?? 0)
                        + (float) ($item->mr_admin_price ?? 0)
                        - (float) ($item->mr_discount_amount ?? 0);
                    $paymentSummary = (float) ($item->payment_summary ?? 0);
                    $remainingPayment = max(0, $totalBill - $paymentSummary);
                    $paymentStatus = $paymentSummary >= $totalBill
                        ? 'Paid'
                        : 'Unpaid - ' . formatRupiah($remainingPayment);
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $memberName }}<br>{{ $memberCode }}</td>
                    <td>{{ $item->package_name ?? '-' }}</td>
                    <td>{{ $isAllClub ? 'All Club' : 'One Club' }}</td>
                    <td>{{ $item->branch_store_name ?? '-' }}</td>
                    <td>{{ $membershipStatus }}</td>
                    <td>
                        {{ DateFormat($item->start_date, 'DD MMMM YYYY') }} -
                        {{ $expiredDate ? DateFormat($expiredDate, 'DD MMMM YYYY') : '-' }}
                    </td>
                    <td>{{ $paymentStatus }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
