<div class="row mb-5">
    <div class="col-xl-12">
        <form action="{{ route('trainer-session-check-in.store') }}" method="POST" enctype="multipart/form-data" id="trainer-session-check-in-form" autocomplete="off">
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
                            <input type="text" name="card_number" id="trainerSessionCardNumberInput" class="form-control" autofocus autocomplete="off">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#trainerSessionQrModal">
                                <i class="fa fa-qrcode me-1"></i> Tampilkan QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="trainerSessionQrModal" tabindex="-1" aria-labelledby="trainerSessionQrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="trainerSessionQrModalLabel">QR Trainer Session Check In / Check Out</h5>
                    <small class="text-muted">Scan melalui landing page Level FIT</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="trainerSessionQr" class="d-flex justify-content-center" aria-live="polite"></div>
                <div id="trainerSessionQrStatus" class="text-muted mt-3">Menyiapkan QR code...</div>
                <button type="button" id="refreshTrainerSessionQr" class="btn btn-outline-primary mt-3 px-4">
                    <i class="fa fa-refresh me-1"></i> Refresh QR
                </button>
                <p class="text-muted small mt-3 mb-0">QR diperbarui otomatis selama pop-up ini terbuka.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('trainer-session-check-in-form');
        const cardInput = document.getElementById('trainerSessionCardNumberInput');

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrContainer = document.getElementById('trainerSessionQr');
        const qrStatus = document.getElementById('trainerSessionQrStatus');
        const refreshButton = document.getElementById('refreshTrainerSessionQr');
        const qrModal = document.getElementById('trainerSessionQrModal');
        let refreshTimer;
        let modalIsOpen = false;

        async function refreshQr() {
            qrStatus.textContent = 'Memperbarui QR code...';
            refreshButton.disabled = true;

            try {
                const response = await fetch(@json(route('trainer-session-check-in.qr-token')), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('Gagal mengambil QR code.');

                const data = await response.json();
                if (!modalIsOpen) return;

                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: data.value,
                    width: 240,
                    height: 240,
                    correctLevel: QRCode.CorrectLevel.M
                });
                qrStatus.textContent = 'Aktif sampai ' + new Date(data.expires_at).toLocaleTimeString('id-ID');
                clearTimeout(refreshTimer);
                refreshTimer = setTimeout(refreshQr, 45000);
            } catch (error) {
                qrStatus.textContent = error.message;
            } finally {
                refreshButton.disabled = false;
            }
        }

        refreshButton.addEventListener('click', refreshQr);
        qrModal.addEventListener('shown.bs.modal', function() {
            modalIsOpen = true;
            refreshQr();
        });
        qrModal.addEventListener('hidden.bs.modal', function() {
            modalIsOpen = false;
            clearTimeout(refreshTimer);
            qrContainer.innerHTML = '';
            qrStatus.textContent = 'Menyiapkan QR code...';
            window.location.reload();
        });
    });
</script>


<div class="row">
    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered" border="1" style="text-align: center;" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Package Name</th>
                        <th>Trainer Name</th>
                        <th>Branch</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->member_code }}</td>
                            <td>{{ $item->member_name }}</td>
                            <td><h6>{{ $item->package_name }}</h6></td>
                            <td>{{ $item->trainer_name }}</td>
                            <td>{{ $item->branch_store_name ?? '-' }}</td>
                            <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No data found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

