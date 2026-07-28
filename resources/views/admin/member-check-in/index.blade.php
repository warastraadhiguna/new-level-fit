<div class="row mb-5">
    <div class="col-xl-12">
        <form action="{{ route('member-check-in.toggle-by-card-number') }}" method="POST" enctype="multipart/form-data" autocomplete="off" id="member-check-in-form">
            @csrf
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-xl-5 col-md-7">
                    <div class="mb-0">
                        <p>Card Number</p>
                        <div class="input-group">
                            <input type="text" name="card_number" id="memberCardNumberInput" class="form-control" autofocus autocomplete="off">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#memberCheckInQrModal">
                                <i class="fa fa-camera me-1"></i> Scan QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="memberCheckInQrModal" tabindex="-1" aria-labelledby="memberCheckInQrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="memberCheckInQrModalLabel">Scan QR Card Member</h5>
                    <small class="text-muted">Arahkan kamera ke QR card number milik member</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="memberQrReader" style="max-width: 420px; margin: 0 auto;"></div>
                <div id="memberQrScannerStatus" class="text-muted mt-3">Tekan tombol untuk mengaktifkan kamera.</div>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" id="startMemberQrScanner" class="btn btn-primary px-4">
                        <i class="fa fa-camera me-1"></i> Mulai Scan
                    </button>
                    <button type="button" id="stopMemberQrScanner" class="btn btn-outline-secondary d-none">Hentikan Kamera</button>
                </div>
                <div class="mt-3">
                    <label for="memberQrImage" class="form-label text-muted small">Atau pilih gambar QR</label>
                    <input type="file" id="memberQrImage" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('member-check-in-form');
        const cardInput = document.getElementById('memberCardNumberInput');

        if (!form) {
            return;
        }

        let isSubmitting = false;

        const focusCardInput = function() {
            if (!cardInput || cardInput.readOnly || document.querySelector('.modal.show')) {
                return;
            }

            cardInput.focus();
        };

        const resetSubmitState = function() {
            isSubmitting = false;

            if (cardInput) {
                cardInput.readOnly = false;
            }

            focusCardInput();
        };

        form.addEventListener('submit', function(event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            isSubmitting = true;

            if (cardInput) {
                cardInput.value = cardInput.value.trim();
                cardInput.readOnly = true;
            }
        });

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                focusCardInput();
            }
        });

        window.addEventListener('focus', focusCardInput);
        window.addEventListener('pageshow', resetSubmitState);
        document.addEventListener('click', function(event) {
            if (!event.target.closest('a, button, input, select, textarea, label')) {
                focusCardInput();
            }
        });

        focusCardInput();
    });
</script>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrModal = document.getElementById('memberCheckInQrModal');
        const form = document.getElementById('member-check-in-form');
        const cardInput = document.getElementById('memberCardNumberInput');
        const status = document.getElementById('memberQrScannerStatus');
        const startButton = document.getElementById('startMemberQrScanner');
        const stopButton = document.getElementById('stopMemberQrScanner');
        const imageInput = document.getElementById('memberQrImage');
        const scanner = new Html5Qrcode('memberQrReader');
        let isRunning = false;
        let isSubmitting = false;

        function submitCardNumber(decodedText) {
            const cardNumber = decodedText.trim();
            if (!cardNumber || isSubmitting) return;

            isSubmitting = true;
            cardInput.value = cardNumber;
            status.textContent = 'QR ditemukan. Memproses card number...';
            status.className = 'text-success mt-3';

            const submit = function() { form.requestSubmit(); };
            if (isRunning) scanner.stop().then(submit).catch(submit);
            else submit();
        }

        startButton.addEventListener('click', async function() {
            startButton.disabled = true;
            status.textContent = 'Meminta izin kamera...';
            try {
                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    submitCardNumber,
                    function() {}
                );
                isRunning = true;
                startButton.classList.add('d-none');
                stopButton.classList.remove('d-none');
                status.textContent = 'Kamera aktif. Arahkan ke QR member.';
            } catch (error) {
                startButton.disabled = false;
                status.textContent = 'Kamera tidak dapat dibuka. Periksa izin kamera atau pilih gambar QR.';
                status.className = 'text-danger mt-3';
            }
        });

        stopButton.addEventListener('click', async function() {
            if (isRunning) await scanner.stop();
            isRunning = false;
            startButton.disabled = false;
            startButton.classList.remove('d-none');
            stopButton.classList.add('d-none');
            status.textContent = 'Kamera dihentikan.';
        });

        imageInput.addEventListener('change', async function(event) {
            const file = event.target.files[0];
            if (!file) return;
            try {
                if (isRunning) {
                    await scanner.stop();
                    isRunning = false;
                }
                submitCardNumber(await scanner.scanFile(file, true));
            } catch (error) {
                status.textContent = 'QR tidak ditemukan pada gambar.';
                status.className = 'text-danger mt-3';
            }
        });

        qrModal.addEventListener('hidden.bs.modal', async function() {
            if (isRunning) await scanner.stop().catch(function() {});
            window.location.reload();
        });
    });
</script>

<div class="row">
    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered" border="1" style="text-align: center;" height="2px" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Branch</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $item)
                        <tr>
                            <td>{{  $loop->iteration }}</td>
                            <td>{{ $item->member_code ?? '-' }}</td>
                            <td>{{ $item->member_name }}</td>
                            <td>{{ $item->branch_store_name }}</td>
                            <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
