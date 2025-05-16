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
                    <th>Nick Name</th>
                    <th>Phone Number</th>
                    <th>Member Number</th>
                    <th>Card Number</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Email</th>
                    <th>Instagram</th>
                    <th>Emergency Contact</th>
                    <th>Emergency Contact Name</th>
                    <th>Address</th>
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
                            <span>{{ $item->nickname }}</span>
                        </td>
                        <td>
                            <span>{{ $item->phone_number ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->member_code ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->card_number ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ DateFormat($item->born, 'DD MMMM YYYY') ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->gender ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->email ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->ig ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->emergency_contact ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->ec_name ?? 'No Data' }}</span>
                        </td>
                        <td>
                            <span>{{ $item->address ?? 'No Data' }}</span>
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
