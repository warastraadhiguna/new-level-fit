<table>
    <thead>
        <tr><th colspan="10">Revenue Report {{ $fromDate }} to {{ $toDate }}</th></tr>
        <tr>
            <th>No</th>
            <th>Date</th>
            <th>Type</th>
            <th>Reference</th>
            <th>Member / Customer</th>
            <th>Member Code</th>
            <th>Package / Description</th>
            <th>Payment Method</th>
            <th>Staff</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($transactions as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->transaction_at }}</td>
                <td>{{ $item->category }}</td>
                <td>{{ $item->reference_number }}</td>
                <td>{{ $item->customer_name }}</td>
                <td>{{ $item->customer_code }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->payment_method }}</td>
                <td>{{ $item->staff_name }}</td>
                <td>{{ $item->amount }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="9"><strong>Total</strong></td>
            <td><strong>{{ $transactions->sum('amount') }}</strong></td>
        </tr>
    </tbody>
</table>
