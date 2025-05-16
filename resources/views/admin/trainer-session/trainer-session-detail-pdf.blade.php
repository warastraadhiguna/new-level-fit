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
        <h5>Trainer Session Detail</h4>
        </h5>
    </center>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>No</th>
                <th>Member Full Name</th>
                <th>Member Code</th>
                <th>Member Phone Number</th>
                <th>Trainer Name</th>
                <th>Session Total</th>
                <th>Remaining Session</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $i=1 @endphp
            @foreach ($trainerSession as $item)
                <tr>
                    @php
                        $remainingSessions = optional($item->trainerSession)->remaining_session ?? 0;
                        $remainingSessions = $item->trainerSessionCheckIn->count();
                        $totalSessions = $item->trainerPackages->number_of_session;
                        $result = $remainingSessions - $totalSessions;
                    @endphp
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ !empty($item->members->full_name) ? $item->members->full_name : 'Member has  been deleted' }}
                    </td>
                    <td>
                        {{ !empty($item->members->member_code) ? $item->members->member_code : 'Member has  been deleted' }}
                    </td>
                    <td>
                        {{ !empty($item->personalTrainers->full_name) ? $item->personalTrainers->full_name : 'Trainer has  been deleted' }}
                    </td>
                    <td>
                        {{ !empty($item->trainerPackages->package_name) ? $item->trainerPackages->package_name : 'Trainer has  been deleted' }}
                    </td>
                    <td>{{ $item->start_date }}</td>
                    <td>
                        {{ !empty($item->trainerPackages->number_of_session) ? $item->trainerPackages->number_of_session : 'Trainer package has  been deleted' }}
                    </td>
                    <td>
                        {{ abs($result) }}
                    </td>
                    <td>
                        @if ($result == 0)
                            <div class="badge bg-danger">
                                Trainer session is Over
                            </div>
                        @else
                            <div class="badge bg-primary">
                                Running
                            </div>
                        @endif
                    </td>
                    <td>
                        {{ !empty($item->users->full_name) ? $item->users->full_name : 'User has  been deleted' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
