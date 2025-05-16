<!DOCTYPE html>
<html lang="en">

<head>
    <title>Private Training Agreement</title>
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
        <img src="https://levelfit.warastra-adhiguna.online/LEVELFIT.png" class="img-fluid" width="100" alt="">
    </div> --}}

    <h4>LEVELFIT</h4>
    <h5>PRIVATE TRAINING AGREEMENT</h5>

    <table>
        <tbody>
            <tr>
                <td style="width: 150px;">No Member: {{ $trainerSession->member_code }}</td>
                <td style="width: 150px;">Club: Semarang</td>
                <td style="width: 150px;">Nama FC: {{ $trainerSession->fc_name }}</td>
            </tr>
        </tbody>
    </table>

    <p class="text-center mt-3">DATA ANGGOTA</p>

    <table>
        <tbody>
            <tr>
                <td style="width: 150px;">Nama Lengkap</td>
                <td>{{ $trainerSession->member_name }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>{{ $trainerSession->gender }}</td>
            </tr>
            <tr>
                <td>Nomor HP</td>
                <td>{{ $trainerSession->member_phone }}</td>
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

    <p class="text-center mt-3">MEMBERSHIP TYPE AND PAYMENT METHOD</p>

    <table>
        <tbody>
            <tr>
                <td>Type Program</td>
                <td>{{ $trainerSession->package_name }}</td>
                <td>PT By</td>
                <td>{{ $trainerSession->trainer_name }}</td>
            </tr>
            <tr>
                <td>Jumlah Paket Session</td>
                <td>{{ $trainerSession->number_of_session }} Session</td>
                <td>Tanggal mulai program ini</td>
                <td>{{ DateFormat($trainerSession->start_date, 'DD MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td>Harga</td>
                <td>{{ formatRupiah($trainerSession->pt_package_price) }}</td>
                <td>Tanggal berakhir program ini</td>
                <td>{{ DateFormat($trainerSession->expired_date, 'DD MMMM YYYY') }}</td>
            </tr>
        </tbody>
    </table>

    <p class="mt-4">
        <b>SYARAT DAN KETENTUAN BERLAKU</b>
    </p>

    <div class="row">
        <div class="col-12">
            <ol type="1" style="font-size: 13px;">
                <li>Anggota diwajibkan untuk memberikan TANDA TANGAN sebelum menjalankan Program Private Training</li>
                <li>Semua jasa jasa pelayanan dalam Program Private Training harus sudah diselesaikan pada atau sebelum
                    masa perjanjian berakhir sesuai dengan ketentuan yang telah ditetapkan di atas.</li>
                <li>Sesi program diatas dinyatakan hangus apabila tidak dapat terselesaikan pada masa berakhirnya
                    Program yang telah disepakati, dalam hal ini tidak ada pengembalian uang dan atau pelatihan, dan
                    Program ini tidak dapat dipindah tangankan.</li>
                <li>Penyesuaian jangka waktu Program ini dianggap sah apabila MANAJEMEN CLUB menyetujui</li>
                <li>LEVELFIT berhak untuk mengatur dan menyediakan pelatih pengganti yang sesuai dan mematuhi
                    persyaratan dalam pelaksanaan Program Private Training apabila terjadi halangan atau perubahan pada
                    jadwal dari pelatih anggota</li>
                <li>Pembayaran untuk Program Private Training tidak dapat dikembalikan, tidak dapat dipindah tangankan,
                    dan tidak dapat diperhitungkan sebagai perhitungan kompensasi biaya lainnya</li>
                <li>Anggota wajib untuk memberitahukan 12 jam sebelumnya apabila terjadi pembatalan pelaksanaan sesi
                    Program ini, apabila tidak ada pemberitahuan maka anggota akan tetap dikenakan biaya pelaksanaan
                    Program tersebut</li>
                <li>MANAJEMEN CLUB mempunyai kewenangan untuk mengubah maupun menambahkan peraturan-peraturan yang ada
                    dan yang diberlakukan didalam klub sesuai dengan kondisi dan kebutuhan MANAJEMEN CLUB (apabila
                    diperlukan) tanpa wajib memberitahukan dan tanpa wajib mendapat persetujuan terlebih dahulu baik
                    kepada maupun dari ANGGOTA CLUB dan/atau TAMU CLUB, dan semua pengubahan atau penambahan tersebut
                    mengikat ANGGOTA CLUB dan/atau TAMU CLUB untuk mematuhinya setelah diberlakukan oleh MANAJEMEN CLUB.
                </li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-center">
            <div class="d-flex justify-content-center mt-4">
                <table class="mt-4" style="border: none;">
                    <tr style="border: none;">
                        <td style="border: none; width: 70%;"></td>
                        <td style="border: none; text-align: center;">
                            <hr style="border: 1px solid black;" />
                            Paraf / Tanda tangan Anggota
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
