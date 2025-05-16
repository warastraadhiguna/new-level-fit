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
        <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="myTable"
            border="1">
            <thead>
                <tr style="background-color: #f5e400;">
                    <th>No</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Date</th>
                    <th>Package Name</th>
                    <th>Package Price</th>
                    <th>Session Total</th>
                    <th>Remaining Session</th>
                    <th>Method Payment</th>
                    <th>PT by</th>
                    <th>Fitness Consultant</th>
                    <th>Staff</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trainerSessions as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <h6>{{ $item->member_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->member_phone }}</h6>
                        </td>
                        <td>
                            <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-{{ DateFormat($item->max_end_date, 'DD MMMM YYYY') }}
                            </h6>
                        </td>
                        <td>
                            <h6>{{ $item->package_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->ts_package_price }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->ts_number_of_session }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->remaining_sessions }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->method_payment_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->trainer_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->fc_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->staff_name }}</h6>
                        </td>
                        <td>
                            {{ $item->expired_date_status }}
                        </td>
                        <td>
                            <h6>{{ $item->description }}</h6>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
