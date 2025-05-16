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
                <th>Member Name</th>
                <th>Member Code</th>
                <th>Phone Number</th>
                <th>gender</th>
                <th>Address</th>
                <th>Description</th>
                <th>Staff</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($members as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->member_name }}</td>
                    <td>{{ $item->member_code }}</td>
                    <td>{{ $item->phone_number }}</td>
                    <td>{{ $item->gender }}</td>
                    <td>{{ $item->address }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->users->full_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
