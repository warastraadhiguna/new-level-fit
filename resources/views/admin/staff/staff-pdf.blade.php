<!DOCTYPE html>
<html>

<head>
    <title>Staff Report</title>
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
        <h5>Staff Report</h4>
        </h5>
    </center>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>No</th>
                <th>Staff Name</th>
                <th>Gender</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($administrator->merge($customerService)->merge($personalTrainer)->merge($administrator) as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->full_name }}</td>
                    <td>{{ $item->gender }}</td>
                    <td>{{ $item->role }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
