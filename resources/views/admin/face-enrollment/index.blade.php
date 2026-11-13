<div class="col-12">
    <div class="mb-4"><h3>Pendaftaran Wajah</h3><p class="text-muted">Daftarkan wajah pegawai untuk absensi otomatis. Pilih pegawai, lalu tambahkan 3 contoh: depan, sedikit kiri, dan sedikit kanan. Maksimal 5 contoh.</p></div>
    @if (!$faceReady)<div class="alert alert-warning">Tabel profil atau contoh wajah belum tersedia. Hubungi administrator untuk menyiapkan enrollment.</div>@endif
    <dialog id="faceResultDialog" class="face-result-dialog" aria-labelledby="faceResultTitle">
        <div id="faceResultIcon" class="face-result-icon" aria-hidden="true"></div>
        <h4 id="faceResultTitle" class="mt-3">Pendaftaran Wajah</h4>
        <p id="faceResult" class="mb-0" aria-live="polite"></p>
        <form method="dialog" class="mt-4"><button type="submit" class="btn btn-primary w-100" autofocus>Tutup</button></form>
    </dialog>
    <div class="row">
        <div class="col-lg-8"><div class="card"><div class="card-body">
            <h4 class="mb-3">Kamera selfie</h4>
            <div class="face-camera"><video id="faceVideo" autoplay playsinline muted></video><p id="faceCameraMessage">Mengaktifkan kamera…</p></div>
            <div class="d-flex flex-wrap mt-3" style="gap:10px"><button type="button" id="faceCapture" class="btn btn-primary" disabled>Ambil Foto</button><button type="button" id="faceCameraRetry" class="btn btn-outline-primary">Aktifkan Kamera</button></div>
            <p class="text-muted mt-3 mb-0">Pastikan hanya satu wajah terlihat jelas dan mendapat pencahayaan yang cukup.</p>
        </div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-body">
            <label for="faceEmployee" class="form-label">Pegawai</label>
            <select id="faceEmployee" class="form-control" @if(!$faceReady) disabled @endif>
                <option value="">Pilih nama pegawai</option>
                @foreach ($employees as $employee)<option value="{{ $employee->id }}" data-enrolled="{{ $employee->enrolled ? '1' : '0' }}" data-count="{{ $employee->sample_count }}">{{ $employee->full_name }} · {{ ($employee->position ?: 'Pegawai') }}{{ $employee->branch_name ? ' · '.$employee->branch_name : '' }}</option>@endforeach
            </select>
            @if ($employees->isEmpty())<p class="text-muted mt-2">Tidak ada pegawai aktif yang dapat didaftarkan.</p>@endif
            <span id="faceStatus" class="badge badge-secondary mt-3">Pilih pegawai terlebih dahulu</span>
            <p id="faceSampleGuide" class="small text-muted mt-2">Disarankan 3 contoh. Foto pendaftaran tidak disimpan; hanya ciri wajah.</p>
            <div id="faceSampleList" class="mt-3" aria-live="polite"></div>
            <label for="facePose" class="form-label mt-3">Posisi wajah</label>
            <select id="facePose" class="form-control"><option value="front">Menghadap depan</option><option value="left">Sedikit menoleh kiri</option><option value="right">Sedikit menoleh kanan</option><option value="additional">Contoh tambahan / penampilan baru</option></select>
            <h5 class="mt-4">Hasil capture</h5>
            <div class="face-preview"><img id="facePhoto" class="d-none" alt="Foto hasil capture"><span id="facePlaceholder" class="text-muted">Belum ada foto</span></div>
            <button type="button" id="faceEnroll" class="btn btn-success mt-3 w-100" data-role-route="{{ route('face-enrollment.store') }}" data-role-method="POST" disabled>Tambah Contoh Wajah</button>
            <p class="small text-muted mt-3 mb-0">Contoh baru ditambahkan tanpa mengganti contoh lama. Pastikan identitas benar. Hapus contoh yang tidak sesuai; menghapus semuanya membuat pegawai tidak dapat absen sampai didaftarkan lagi.</p>
        </div></div></div>
    </div>
</div>
<style>
.face-result-dialog{border:0;border-radius:18px;padding:28px;width:min(440px,calc(100% - 32px));max-height:85vh;overflow-y:auto;box-shadow:0 24px 80px #10254040;color:#17243b;text-align:center}
.face-result-dialog::backdrop{background:rgba(15,23,42,.55)}
.face-result-icon{width:58px;height:58px;margin:auto;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700}
.face-result-success .face-result-icon{background:#dcfce7;color:#15803d}.face-result-failed .face-result-icon{background:#fee2e2;color:#b91c1c}

.face-camera{aspect-ratio:4/3;background:#15243b;border-radius:12px;position:relative;overflow:hidden}.face-camera video{width:100%;height:100%;object-fit:contain;transform:scaleX(-1)}#faceCameraMessage{position:absolute;top:40%;left:8%;right:8%;text-align:center;color:white}.face-preview{aspect-ratio:4/3;background:#f4f6fa;display:flex;align-items:center;justify-content:center;border-radius:12px;overflow:hidden}.face-preview img{height:100%;width:100%;object-fit:contain}
</style>
<script>
window.faceEnrollmentConfig = {url:@json(route('face-enrollment.store')),samplesUrl:@json(route('face-enrollment.samples')),ready:@json($faceReady),csrf:@json(csrf_token())};
</script>
<script src="{{ asset('js/face-enrollment.js') }}?v={{ filemtime(public_path('js/face-enrollment.js')) }}" defer></script>
