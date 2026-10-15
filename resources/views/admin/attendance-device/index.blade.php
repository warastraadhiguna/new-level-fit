<div class="col-lg-8"><div class="card"><div class="card-body">
<h3>Perangkat Absensi</h3><p class="text-muted">Cabang: {{ optional(Auth::user()->branchStore)->name }} · Satu perangkat aktif per cabang.</p>
@if(session('message'))<div class="alert alert-info">{{ session('message') }}</div>@endif
@if(session('pairing_code'))<div class="alert alert-success"><strong>Kode aktivasi: <span style="font-size:1.4rem;letter-spacing:2px">{{ session('pairing_code') }}</span></strong><p class="mb-0 mt-2">Buka halaman absensi pada HP yang dipilih, lalu masukkan kode ini. Kode hanya ditampilkan sekarang; jangan bagikan kepada pegawai lain.</p></div>@endif
@php $active = $device && $device->token_hash && $device->expires_at && \Carbon\Carbon::parse($device->expires_at,'UTC')->isFuture(); @endphp
<div class="mb-4"><span class="badge {{ $active ? 'badge-success':'badge-secondary' }}">{{ $active ? 'Perangkat aktif':'Belum ada perangkat aktif' }}</span>@if($device && $device->label)<p class="mt-2 mb-0">{{ $device->label }}</p>@endif</div>
@if ($device && isset($device->owner_login_branch_store_id))
<div class="bg-light rounded p-3 mb-4">
    <h5>Owner pengaturan terakhir</h5>
    <p class="mb-1">{{ $device->owner_name ?? ('Owner #'.$device->owner_id) }}</p>
    <p class="mb-1">Cabang login Owner: <strong>{{ $device->owner_login_branch_name ?? ('Cabang #'.$device->owner_login_branch_store_id) }}</strong></p>
    @if (!empty($device->owner_recorded_at))<small class="text-muted">Dicatat {{ \Carbon\Carbon::parse($device->owner_recorded_at,'UTC')->setTimezone(config('app.timezone'))->format('d M Y H:i:s') }}</small>@endif
</div>
@endif
<form method="POST" action="{{ route('attendance-device.pair') }}" @if($active) onsubmit="return confirm('Siapkan HP pengganti? HP lama akan kehilangan akses setelah kode dipakai pada HP baru.')" @endif>
@csrf<label class="form-label" for="deviceLabel">Nama perangkat</label><input id="deviceLabel" name="label" maxlength="100" required class="form-control mb-3" placeholder="Contoh: HP Absensi Meja Depan" value="{{ old('label') }}">
@error('label')<p class="text-danger">{{ $message }}</p>@enderror
<button class="btn btn-primary">Buat Kode Aktivasi</button></form>
<form method="POST" action="{{ route('attendance-device.revoke') }}" class="mt-3" onsubmit="return confirm('Cabut akses HP dan seluruh kode aktivasi cabang ini sekarang?')">@csrf<button class="btn btn-outline-danger">Cabut Akses Perangkat</button></form>
<p class="mt-3">Halaman aktivasi: <a href="{{ config('face.attendance_url') }}" target="_blank" rel="noopener">Buka di perangkat absensi</a></p>
<p class="text-muted small mt-4 mb-0">Aktivasi terikat ke browser HP. Menghapus cookie atau mengganti browser memerlukan aktivasi ulang oleh Owner. Pegawai tidak perlu login untuk setiap absensi. Untuk perangkat khusus, gunakan mode kiosk HP.</p>
</div></div></div>
