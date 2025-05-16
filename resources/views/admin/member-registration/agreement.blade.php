<!DOCTYPE html>
<html lang="en">

<head>
    <title>Membership Agreement</title>
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
    {{-- <div class="img text-end">
        <img src="https://gym.gelorasports.com/logokecil.png" class="img-fluid" width="200" alt="">
    </div> --}}

    <h4>{{ config('app.name') }}</h4>
    <h5>MEMBERSHIP AGREEMENT</h5>

    <table>
        <tbody>
            <tr>
                <td style="width: 150px;">No Agreement: {{ $memberRegistration->member_code }}</td>
                <td style="width: 150px;">Club: Semarang</td>
                <td style="width: 150px;">Nama FC: {{ $memberRegistration->fc_name }}</td>
            </tr>
        </tbody>
    </table>

    <p class="text-center mt-3">PERSONAL DETAIL</p>

    <table>
        <tbody>
            <tr>
                <td style="width: 150px;">Nama Lengkap</td>
                <td>{{ $memberRegistration->member_name }}</td>
            </tr>
            <tr>
                <td>Nama Panggilan</td>
                <td>{{ $memberRegistration->nickname }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>{{ $memberRegistration->gender }}</td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td>
                <td>{{ DateFormat($memberRegistration->born, 'DD MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td>Nomor HP</td>
                <td>{{ $memberRegistration->phone_number }}</td>
            </tr>
            <tr>
                <td>Nomor Kontak Darurat</td>
                <td>{{ $memberRegistration->emergency_contact }}</td>
            </tr>
            <tr>
                <td>Nama Kontak Darurat</td>
                <td>{{ $memberRegistration->ec_name }}</td>
            </tr>
            <tr>
                <td>Email</td>
                <td>{{ $memberRegistration->email }}</td>
            </tr>
            <tr>
                <td>Instagram</td>
                <td>{{ $memberRegistration->ig }}</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>{{ $memberRegistration->address }}</td>
            </tr>
        </tbody>
    </table>

    <p class="text-center mt-3">MEMBERSHIP TYPE AND PAYMENT METHOD</p>

    <table>
        <tbody>
            <tr>
                <td>No Member</td>
                <td>{{ $memberRegistration->member_code }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Tanggal Pembayaran</td>
                <td>{{ DateFormat($memberRegistration->start_date, 'DD MMMM YYYY') }}</td>
                <td>Harga Paket</td>
                <td>{{ formatRupiah($memberRegistration->mr_package_price) }}</td>
            </tr>
            <tr>
                <td>Masa Aktif</td>
                <td>{{ DateFormat($memberRegistration->start_date, 'DD MMMM YYYY') }}</td>
                <td>Biaya Administrasi</td>
                <td>{{ formatRupiah($memberRegistration->mr_admin_price) }}</td>
            </tr>
            <tr>
                <td>Expired Paket</td>
                <td>{{ DateFormat($memberRegistration->expired_date, 'DD MMMM YYYY') }}</td>
                @php
                    $totalBayar = $memberRegistration->mr_package_price + $memberRegistration->mr_admin_price;
                @endphp
                <td>Total Bayar</td>
                <td>{{ formatRupiah($totalBayar) }}</td>
            </tr>
            <tr>
                <td>Nama Paket</td>
                <td>{{ $memberRegistration->package_name }}</td>
                <td>Metode Pembayaran</td>
                <td>{{ $memberRegistration->method_payment_name }}</td>
            </tr>
        </tbody>
    </table>

    <div class="row mt-4">
        <div class="col-12 text-center">
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

    <p class="text-center mt-4">
        <b>DECLARATION</b>
    </p>

    <div class="row">
        <div class="col-12">
            <p style="font-size: 13px;" class="text-center">Dengan ditandatanganinya "MEMBERSHIP AGREEMENT" ini, maka
                ANGGOTA KLUB dengan
                ini
                mengakui dan menyetujui hal-hal sebagai berikut dalam keadaan sadar dan tanpa adanya
                paksaan dari pihak manapun, dengan menjadi member LEVELFIT akan mematuhi peraturan
                dan tata tertib serta peraturan yang ada dan diberlakukan di klub.
                Telah menerima, membaca, dan memahami dengan baik salinan Peraturan dan Tata tertib
                serta peraturan-peraturan yang ada dan diberlakukan di klub serta menyatakan bahwa
                MEMBERSHIP AGREEMENT dan segala peraturan yang melekat dan menjadi satu kesatuan
                dengannya adalah sah dan mengikat secara hukum.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-center">
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
