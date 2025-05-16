<!DOCTYPE html>
<html>

<head>
    <title>Members Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<body>
    <style type="text/css">
        table tr td,
        table tr th {
            font-size: 9pt;
        }
    </style>
    <center>
        <h5>Members Report</h4>
        </h5>
    </center>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>No</th>
                <th>Member Data</th>
                <th>Package Data</th>
                <th>Days</th>
                <th>Start Date</th>
                <th>Expired Date</th>
                <th>Description</th>
                <th>Status</th>
                <th>Staff</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($memberRegistrations as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $item->member_name }}, <br />
                        {{ $item->member_code }},<br />
                        {{ $item->phone_number }}
                    </td>
                    <td>
                        {{ $item->package_name }}, <br />
                        {{ formatRupiah($item->package_price) }}, <br />
                        {{ $item->days }} Days
                    </td>
                    <td>{{ $item->start_date }}</td>
                    <td>{{ $item->expired_date }}</td>
                    <td>{{ $item->description }}</td>
                    <td>
                        @if ($item->status == 'Running')
                            <span class="badge badge-primary">Running</span>
                        @else
                            <span class="badge badge-danger">Over</span>
                        @endif
                    </td>
                    <td>
                        {{ $item->staff_name }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
