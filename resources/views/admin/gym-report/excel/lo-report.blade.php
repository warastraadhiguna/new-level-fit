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
                <tr style="background-color: #f5e400;">
                    <th>No</th>
                    <th>PT Name</th>
                    <th>Member Name</th>
                    <th>LO Start</th>
                    <th>LO End</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $item->pt_name }}
                    </td>
                    <td>
                        {{ $item->member_name }}
                    </td>
                    <td>
                        {{ DateFormat($item->lo_start_date, 'DD MMMM YYYY, H:mm:ss') }}
                    </td>
                    <td>
                        {{ DateFormat($item->lo_end, 'DD MMMM YYYY, H:mm:ss') }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
