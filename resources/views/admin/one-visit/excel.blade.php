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
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Staff</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($memberOneVisit as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <h6>{{ $item->member_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->phone_number }}</h6>
                        </td>
                        <td>
                            <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}
                            </h6>
                        </td>
                        <td>
                            @if ($item->status == 'Running')
                                <span>Running</span>
                            @else
                                <span>Expired</span>
                            @endif
                        </td>
                        <td>
                            <h6>{{ $item->staff_name }}</h6>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
