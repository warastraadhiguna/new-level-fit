<div class="col-12"><div class="card"><div class="card-header"><h4 class="card-title">Pengaturan Akses Role</h4></div><div class="card-body">
    <p>Pengaturan untuk <strong>{{ optional(Auth::user()->branchStore)->name }}</strong>. Centang fitur yang boleh diakses setiap role. Hanya Owner yang dapat mengubah pengaturan ini.</p>
    <p class="text-muted">Owner selalu memiliki akses. PT tetap hanya dapat melihat; unduh Excel dan perangkat absensi tetap khusus Owner. Tanda — berarti fitur tidak tersedia untuk role tersebut.</p>
    @unless($ready)<div class="alert alert-warning">Tabel pengaturan belum tersedia. Jalankan migration atau SQL pengaturan akses role sebelum menyimpan.</div>@endunless
    <form method="POST" action="{{ route('role-feature-access.update') }}">@csrf @method('PUT')
        <div class="table-responsive"><table class="table table-bordered align-middle" style="width:100%">
            <thead><tr><th>Fitur</th>@foreach($roles as $role=>$label)<th class="text-center">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>@foreach($features as $key=>$feature)<tr><th scope="row">{{ $feature['label'] }}@if($key==='tip_tap_transfer')<small class="d-block text-muted">Ekspor data seluruh cabang</small>@endif</th>
                @foreach($roles as $role=>$label)<td class="text-center">
                    @if(in_array($role,$feature['roles'],true))
                        <input type="checkbox" class="form-check-input" name="permissions[{{ $role }}][{{ $key }}]" value="1" aria-label="{{ $label }}: {{ $feature['label'] }}" {{ ($matrix[$role][$key] ?? in_array($role,$feature['defaults'],true)) ? 'checked' : '' }} @unless($ready) disabled @endunless>
                    @else <span class="text-muted" title="Tetap mengikuti batas akses role">—</span> @endif
                </td>@endforeach
            </tr>@endforeach</tbody>
        </table></div>
        <button class="btn btn-primary" @unless($ready) disabled @endunless>Simpan Pengaturan</button>
    </form>
</div></div></div>
