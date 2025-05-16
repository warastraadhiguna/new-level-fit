<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <div class="table-responsive full-data">
        <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="myTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($members as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <span>{{ $item->full_name }}</span>
                        </td>
                        <td>
                            <span>{{ $item->phone_number ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ DateFormat($item->created_at, 'DD MMMM YYYY') ?? 'No Data' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
