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
                    <th>Member Code</th>                    
                    <th>Member Name</th>
                    <th>Trainer Name</th>                    
                    <th>Check In Time</th>
                    <th>Check Out Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->member_code }}
                        </td>                            
                        <td>
                            <h6>{{ $item->member_name }}</h6>
                        </td>
                        <td>
                            {{ $item->trainer_name }}
                        </td>                        
                        <td>
                            {{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}
                        </td>
                        <td>
                            {{ DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
