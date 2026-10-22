<div class="col-lg-8"><div class="card"><div class="card-body">
<h3>Perangkat Absensi</h3><p class="text-muted">Cabang: {{ optional(Auth::user()->branchStore)->name }} · Beberapa perangkat dapat aktif di cabang yang sama.</p>
@if(session('message'))<div class="alert alert-info">{{ session('message') }}</div>@endif
@if(session('pairing_code'))<div class="alert alert-success"><strong>Kode aktivasi: <span style="font-size:1.4rem;letter-spacing:2px">{{ session('pairing_code') }}</span></strong><p class="mb-0 mt-2">Buka halaman absensi pada HP yang dipilih, lalu masukkan kode ini. Kode hanya ditampilkan sekarang; jangan bagikan kepada pegawai lain.</p></div>@endif
@forelse($devices as $device)
@php $active = $device->token_hash && $device->expires_at && \Carbon\Carbon::parse($device->expires_at,'UTC')->isFuture(); @endphp
<div class="border rounded p-3 mb-3">
<h5>{{ $device->label ?: $device->pairing_label ?: ('Perangkat #'.$device->id) }}</h5>
<span class="badge {{ $active ? 'badge-success':'badge-secondary' }}">{{ $active ? 'Aktif' : ($device->pairing_hash && $device->pairing_expires_at && \Carbon\Carbon::parse($device->pairing_expires_at,'UTC')->isFuture() ? 'Menunggu aktivasi' : 'Tidak aktif') }}</span>
@if(isset($device->owner_login_branch_store_id))
<p class="mt-2 mb-1">Owner pengaturan terakhir: {{ $device->owner_name ?? ('Owner #'.$device->owner_id) }}</p>
<p class="mb-1">Cabang login Owner: <strong>{{ $device->owner_login_branch_name ?? ('Cabang #'.$device->owner_login_branch_store_id) }}</strong></p>
@if($device->owner_recorded_at)<small>{{ \Carbon\Carbon::parse($device->owner_recorded_at,'UTC')->setTimezone(config('app.timezone'))->format('d M Y H:i:s') }}</small>@endif
@endif
<form method="POST" action="{{ route('attendance-device.revoke') }}" class="mt-2" onsubmit="return confirm('Cabut akses perangkat ini?')">@csrf<input type="hidden" name="device_id" value="{{ $device->id }}"><button class="btn btn-outline-danger btn-sm">Cabut Akses Perangkat Ini</button></form>
</div>
@empty<p>Belum ada perangkat terdaftar.</p>@endforelse
<form method="POST" action="{{ route('attendance-device.pair') }}">
@csrf<label class="form-label" for="deviceLabel">Nama perangkat</label><input id="deviceLabel" name="label" maxlength="100" required class="form-control mb-3" placeholder="Contoh: HP Absensi Meja Depan" value="{{ old('label') }}">
@error('label')<p class="text-danger">{{ $message }}</p>@enderror
<button class="btn btn-primary">Buat Kode Aktivasi</button></form>

<p class="mt-3">Halaman aktivasi: <a href="{{ config('face.attendance_url') }}" target="_blank" rel="noopener">Buka di perangkat absensi</a></p>
<p class="text-muted small mt-4 mb-0">Aktivasi terikat ke browser HP. Menghapus cookie atau mengganti browser memerlukan aktivasi ulang oleh Owner. Pegawai tidak perlu login untuk setiap absensi. Untuk perangkat khusus, gunakan mode kiosk HP.</p>
</div></div></div>
