@php
    $cameraKey = $cameraKey ?? 'member-photo';
    $inputId = $inputId ?? $cameraKey . '-input';
    $modalId = $cameraKey . '-modal';
    $videoId = $cameraKey . '-video';
    $canvasId = $cameraKey . '-canvas';
    $statusId = $cameraKey . '-status';
    $captureId = $cameraKey . '-capture';
    $retryId = $cameraKey . '-retry';
@endphp

<label for="{{ $inputId }}" class="form-label">Photo</label>
<div class="input-group">
    <input
        class="form-control"
        type="file"
        name="photos"
        id="{{ $inputId }}"
        accept="image/png,image/jpeg"
        onchange="loadFile(event)"
    >
    <button
        type="button"
        class="btn btn-outline-primary"
        data-bs-toggle="modal"
        data-bs-target="#{{ $modalId }}"
    >
        <i class="fa fa-camera me-1"></i> Ambil dari Kamera
    </button>
</div>
<small class="text-muted">Pilih file seperti biasa atau ambil foto langsung menggunakan webcam.</small>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="{{ $modalId }}-label">Ambil Foto Member</h5>
                    <small class="text-muted">Posisikan wajah di tengah kamera, lalu tekan Ambil Foto.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
                <video
                    id="{{ $videoId }}"
                    autoplay
                    muted
                    playsinline
                    style="display:none; width:100%; max-height:65vh; object-fit:contain; background:#111; border-radius:8px;"
                ></video>
                <canvas id="{{ $canvasId }}" class="d-none"></canvas>
                <div id="{{ $statusId }}" class="text-muted py-5">
                    Kamera sedang disiapkan...
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <div>
                    <button type="button" id="{{ $retryId }}" class="btn btn-outline-primary d-none">
                        <i class="fa fa-refresh me-1"></i> Coba Lagi
                    </button>
                    <button type="button" id="{{ $captureId }}" class="btn btn-primary" disabled>
                        <i class="fa fa-camera me-1"></i> Ambil Foto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById(@json($modalId));
    const video = document.getElementById(@json($videoId));
    const canvas = document.getElementById(@json($canvasId));
    const status = document.getElementById(@json($statusId));
    const captureButton = document.getElementById(@json($captureId));
    const retryButton = document.getElementById(@json($retryId));
    const fileInput = document.getElementById(@json($inputId));
    let stream = null;

    if (!modalElement || !video || !canvas || !fileInput) {
        return;
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
        }

        stream = null;
        video.srcObject = null;
        video.style.display = 'none';
        captureButton.disabled = true;
    }

    function showCameraError(message) {
        stopCamera();
        status.textContent = message;
        status.classList.remove('d-none', 'text-muted');
        status.classList.add('text-danger');
        retryButton.classList.remove('d-none');
    }

    async function startCamera() {
        stopCamera();
        retryButton.classList.add('d-none');
        status.textContent = 'Meminta izin kamera...';
        status.classList.remove('d-none', 'text-danger');
        status.classList.add('text-muted');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraError('Kamera tidak tersedia. Gunakan HTTPS atau localhost dan pastikan browser mendukung webcam.');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 960 }
                },
                audio: false
            });

            video.srcObject = stream;
            await video.play();
            video.style.display = 'block';
            status.classList.add('d-none');
            captureButton.disabled = false;
        } catch (error) {
            showCameraError('Kamera tidak dapat dibuka. Periksa izin kamera pada browser, lalu coba lagi.');
        }
    }

    function createPhotoFile() {
        const sourceWidth = video.videoWidth;
        const sourceHeight = video.videoHeight;

        if (!sourceWidth || !sourceHeight) {
            showCameraError('Gambar kamera belum siap. Silakan coba lagi.');
            return;
        }

        const maxDimension = 960;
        const scale = Math.min(1, maxDimension / sourceWidth, maxDimension / sourceHeight);
        canvas.width = Math.max(1, Math.round(sourceWidth * scale));
        canvas.height = Math.max(1, Math.round(sourceHeight * scale));

        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function(blob) {
            if (!blob) {
                showCameraError('Foto gagal diproses. Silakan coba lagi.');
                return;
            }

            const fileName = 'webcam-member-' + Date.now() + '.jpg';
            const photoFile = new File([blob], fileName, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            const transfer = new DataTransfer();
            transfer.items.add(photoFile);
            fileInput.files = transfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));

            stopCamera();
            bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        }, 'image/jpeg', 0.85);
    }

    modalElement.addEventListener('shown.bs.modal', startCamera);
    modalElement.addEventListener('hidden.bs.modal', stopCamera);
    captureButton.addEventListener('click', createPhotoFile);
    retryButton.addEventListener('click', startCamera);
    window.addEventListener('pagehide', stopCamera);
});
</script>
