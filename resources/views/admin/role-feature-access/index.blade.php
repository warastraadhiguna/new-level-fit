<div class="col-12"><div class="card"><div class="card-header"><h4 class="card-title">Pengaturan Akses Role</h4></div><div class="card-body">
    <p>Pengaturan untuk <strong>{{ optional(Auth::user()->branchStore)->name }}</strong>. Hanya Owner yang dapat mengubah izin role.</p>
    <p class="text-muted">Create: tambah data. Read: lihat data. Update: ubah data / restore. Delete: hapus data. Read harus aktif sebelum izin lain dapat dipilih.</p>
    <p class="text-muted">Semua role dapat diberi izin oleh Owner, termasuk PT, HRD, dan Finance. Izin tambahan tidak aktif sampai dicentang. Hanya fitur yang aktif di Secret Branch Store yang ditampilkan. Ekspor Tip-Tap termasuk Read. Excel dan perangkat absensi tetap mengikuti batas khusus Owner.</p>
    @unless($ready)<div class="alert alert-warning">Tabel pengaturan belum tersedia. Jalankan migration atau SQL pengaturan akses role sebelum menyimpan.</div>@endunless
    @if($ready && !$crudReady)<div class="alert alert-warning">Kolom izin CRUD belum tersedia. Jalankan SQL izin CRUD role sebelum menyimpan.</div>@endif
    @php
        $roleActors=[];
        foreach($roles as $role=>$label) $roleActors[$role]=new \App\Models\User(['role'=>$role,'branch_store_id'=>Auth::user()->branch_store_id]);
    @endphp
    <form method="POST" action="{{ route('role-feature-access.update') }}" id="roleCrudForm">@csrf @method('PUT')
        <div class="table-responsive"><table class="table table-bordered align-middle" style="width:100%">
            <thead><tr><th>Fitur</th>@foreach($roles as $role=>$label)<th class="text-center" style="min-width:160px">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>@foreach($features as $key=>$feature)<tr><th scope="row">{{ $feature['label'] }}@if($key==='tip_tap_transfer')<small class="d-block text-muted">Ekspor data seluruh cabang</small>@endif
                @if(in_array($key,['membership_checkin','pt_checkin'],true))<small class="d-block text-muted">Create mencatat check-in / check-out</small>@endif
                @if($key==='lo_cancel')<small class="d-block text-muted">Update membatalkan LO agar dapat didaftarkan ulang. Aktifkan juga Read pada Data Member untuk membuka daftar member.</small>@endif
            </th>
                @foreach($roles as $role=>$label)<td>
                    @if(in_array($role,$feature['roles'],true))
                        <div class="role-crud-cell">
                        @foreach(\App\Support\RoleFeatureAccess::actions() as $action=>$actionLabel)
                            @if(in_array($action,\App\Support\RoleFeatureAccess::actionsFor($key,$role),true))
                                <label class="d-block mb-1 text-nowrap">
                                    <input type="checkbox" class="form-check-input me-1" data-crud-action="{{ $action }}" name="permissions[{{ $role }}][{{ $key }}][{{ $action }}]" value="1" aria-label="{{ $label }}: {{ $feature['label'] }} - {{ $actionLabel }}" {{ $access->allows($roleActors[$role],$key,$action) ? 'checked':'' }} @unless($crudReady) disabled @endunless> {{ $actionLabel }}
                                </label>
                            @endif
                        @endforeach
                        </div>
                    @else <span class="text-muted">Tidak diizinkan cabang</span> @endif
                </td>@endforeach
            </tr>@endforeach</tbody>
        </table></div>
        <button class="btn btn-primary" @unless($crudReady) disabled @endunless>Simpan Pengaturan</button>
    </form>
</div></div></div>
<script>
    document.getElementById('roleCrudForm').addEventListener('change', function (event) {
        const input = event.target;
        if (!input.matches('[data-crud-action]')) return;
        const cell = input.closest('.role-crud-cell');
        if (input.dataset.crudAction === 'read' && !input.checked) {
            cell.querySelectorAll('[data-crud-action]').forEach(function (other) { other.checked = false; });
        } else if (input.checked) {
            cell.querySelector('[data-crud-action="read"]').checked = true;
        }
    });
</script>
