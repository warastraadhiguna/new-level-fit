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
                    <th>Trainer Name</th>
                    <th>Trainer Phone</th>
                    <th>Member Name</th>
                    <th>Member Phone</th>
                    <th>Package Name</th>
                    <th>Date</th>
                    <th>Package Price</th>
                    <th>Number of Days</th>
                    <th>Method Payment</th>
                    <th>Fitness Consultant</th>
                    <th>Staff</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($personalTrainers as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <h6>{{ $item->trainer_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->trainer_phone }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->member_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->member_phone }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->pt_package_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}
                            </h6>
                        </td>
                        <td>
                            <h6>{{ formatRupiah($item->ts_package_price) }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->member_registration_days }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->method_payment_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->fc_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->staff_name }}</h6>
                        </td>
                        <td>
                            @if ($item->leave_day_status == 'Freeze')
                                <h6>Freeze</h6>
                            @else
                                @if ((!$item->check_in_time && !$item->check_out_time) || ($item->check_in_time && $item->check_out_time))
                                    <h6>Not Start</h6>
                                @elseif ($item->check_in_time && !$item->check_out_time)
                                    <h6>Running</h6>
                                @endif
                            @endif
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
