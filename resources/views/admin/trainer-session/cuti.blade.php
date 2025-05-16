<!DOCTYPE html>
<html lang="en">

<head>
    <title>Freeze Membership</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style type="text/css">
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            border: 2px solid black;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
        }

        h4,
        h5 {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="img text-end">
        <img src="https://gym.gelorasports.com/logokecil.png" class="img-fluid" width="200" alt="">
    </div>

    <h4>LEVELFIT</h4>
    <h5>FREEZE MEMBERSHIP</h5>

    <table>
        <tbody>
            <tr>
                <td style="width: 150px;">No Member: {{ $trainerSession->member_code }}</td>
                <td style="width: 150px;">Club: Semarang</td>
                <td style="width: 150px;">Nama FC: {{ $trainerSession->fc_name }}</td>
            </tr>
        </tbody>
    </table>

    <p class="text-center mt-3">PERSONAL DETAIL</p>

    <table>
        <tbody>
            <tr>
                <td style="width: 150px;">Nama Lengkap</td>
                <td>{{ $trainerSession->member_name }}</td>
            </tr>
            <tr>
                <td>Nama Panggilan</td>
                <td>{{ $trainerSession->nickname }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>{{ $trainerSession->gender }}</td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td>
                <td>{{ DateFormat($trainerSession->born, 'DD MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td>Nomor HP</td>
                <td>{{ $trainerSession->phone_number }}</td>
            </tr>
            <tr>
                <td>Email</td>
                <td>{{ $trainerSession->email }}</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>{{ $trainerSession->address }}</td>
            </tr>
        </tbody>
    </table>

    <p class="text-center mt-3">FREEZE PERIOD AND PAYMENT METHOD</p>

    <table>
        <tbody>
            <tr>
                <td>Tanggal Pengajuan</td>
                <td>{{ DateFormat($trainerSession->submission_date_continue, 'DD MMMM YYYY') }}</td>
                <td>Biaya Administrasi</td>
                <td>{{ formatRupiah($trainerSession->total_price_continue) }}</td>
            </tr>
            <tr>
                <td>Periode Cuti</td>
                <td>{{ DateFormat($trainerSession->submission_date_continue, 'DD MMMM YYYY') }}-
                    {{ DateFormat($trainerSession->expired_leave_days, 'DD MMMM YYYY') }}</td>
                <td>Tanggal Pembayaran</td>
                <td>{{ DateFormat($trainerSession->submission_date_continue, 'DD MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td>Expired Member</td>
                <td>{{ DateFormat($trainerSession->expired_date, 'DD MMMM YYYY') }}</td>
                <td>Metode Pembayaran</td>
                <td>{{ $trainerSession->method_payment_name }}</td>
            </tr>
        </tbody>
    </table>

    <div class="row mt-4">
        <div class="col-12 text-center mt-4">
            <div class="d-flex justify-content-center">
                <table class="mt-4" style="border: none;">
                    <tr style="border: none;">
                        <td style="border: none; text-align: center;">
                            <hr style="border: 1px solid black;" />
                            Staff
                        </td>
                        <td style="border: none; width: 50%;"></td>
                        <td style="border: none; text-align: center;">
                            <hr style="border: 1px solid black;" />
                            Member
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
