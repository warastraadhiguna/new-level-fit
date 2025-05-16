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
                    <th>FC Name</th>
                    <th>Member Name</th>
                    <th>Package Name</th>
                    <th>Package Price</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <h6>{{ $item->fc_name }}</h6>
                        </td>
                        <td>
                            <h6>{{ $item->member_name }}</h6>
                        </td>
                        <td>
                            {{ $item->package_name }}
                        </td>
                        <td>
                            {{ FormatRupiah($item->package_price) }}
                        </td>
                        <td>
                            {{ DateFormat($item->created_at, 'DD MMMM YYYY') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
