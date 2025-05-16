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
        <h5>Trainer Session Report</h4>
        </h5>
    </center>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>No</th>
                <th>Member Data</th>
                <th>Trainer Name</th>
                <th>Trainer Package</th>
                <th>Start Date</th>
                <th>Expired Date</th>
                <th>Session Total</th>
                <th>Remaining Session</th>
                <th>Status</th>
                <th>Staff Name</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($result as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $item->member_name }}, <br />
                        {{ $item->member_code }}
                    </td>
                    <td>
                        {{ $item->trainer_name }}
                    </td>
                    <td>
                        {{ $item->package_name }}
                    </td>
                    <td>{{ $item->start_date }}</td>
                    <td>
                        {{ $item->number_of_session }}
                    </td>
                    <td>
                        {{ $item->remaining_sessions }}
                    </td>
                    <td>
                        @if ($item->session_status == 'Running')
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
