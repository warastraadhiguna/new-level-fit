<!DOCTYPE html>
<html>

<head>
    <title>Trainer Session Over Report</title>
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
        <h5>Trainer Session Over Report</h4>
        </h5>
    </center>
    <div>
        @php
            $totalPrice = 0;
            $adminPrice = 0;
        @endphp
        @foreach ($trainerSessionsOver as $item)
            @php
                $totalPrice += $item->total_price;
                $adminPrice += $item->admin_price;
            @endphp
        @endforeach

        <table class="table borderless display dataTable price-table">
            <tbody>
                <tr>
                    <th scope="row">Date</th>
                    <td>
                        @if (!empty($trainerSessionsOver))
                            <?php
                            $earliestDate = \Carbon\Carbon::parse($trainerSessionsOver->min('earliest_created_at'))->format('Y-m-d');
                            $latestDate = \Carbon\Carbon::parse($trainerSessionsOver->max('latest_created_at'))->format('Y-m-d');
                            ?>
                            {{-- <div class="date-section"> --}}
                            {{ $earliestDate }} <b>to</b> {{ $latestDate }}

                            {{-- @foreach ($trainerSessionsOver as $session)
                                    <!-- Your display logic for each session goes here -->
                                @endforeach --}}
                            {{-- </div> --}}
                        @else
                            {{-- <p>No trainer sessions found.</p> --}}
                        @endif
                        </th>
                </tr>
                <tr>
                    <th scope="row">Total Package Price Over</th>
                    <td>{{ formatRupiah($totalPrice) }}</td>
                </tr>
                <tr>
                    <th scope="row">Total Admin Price over</th>
                    <td>{{ formatRupiah($adminPrice) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>No</th>
                <th>Member's Data</th>
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
            @foreach ($trainerSessionsOver as $item)
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
                    <td>{{ $item->expired_date }}</td>
                    <td>
                        {{ $item->number_of_session }}
                    </td>
                    <td>
                        {{ $item->remaining_sessions }}
                    </td>
                    <td>
                        @if ($item->session_status == 'Running')
                            <span>Running</span>
                        @else
                            <span>Over</span>
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
