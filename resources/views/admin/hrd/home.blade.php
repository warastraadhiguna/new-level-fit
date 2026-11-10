<div class="col-lg-8"><div class="card"><div class="card-body"><h3>HRD</h3><p>Cabang: {{ optional(Auth::user()->branchStore)->name ?: 'Belum ditentukan' }}</p>
@if(Auth::user()->branch_store_id && optional(Auth::user()->branchStore)->face_enrollment_enabled)
<p>Kelola data pegawai dan pendaftaran wajah untuk cabang login.</p>
@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'employees.index'))<a class="btn btn-primary" href="{{ route('employees.index') }}">Data Pegawai</a>@endif
@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'face-enrollment.index'))<a class="btn btn-outline-primary" href="{{ route('face-enrollment.index') }}">Pendaftaran Wajah</a>@endif
@else<p>Pengelolaan pegawai belum tersedia untuk cabang ini. Hubungi Owner untuk menentukan cabang dan mengaktifkan fitur wajah.</p>@endif
</div></div></div>
