<li><a href="{{ route('hrd.home') }}"><i class="material-symbols-outlined">home</i><span class="nav-text">HRD</span></a></li>
@if(optional(Auth::user()->branchStore)->face_enrollment_enabled)
<li><a class="has-arrow" href="javascript:void(0);" aria-expanded="false"><i class="material-symbols-outlined">person</i><span class="nav-text">Staff</span></a><ul aria-expanded="false">
<li><a href="{{ route('employees.index') }}">Data Pegawai</a></li>
<li><a href="{{ route('face-enrollment.index') }}">Pendaftaran Wajah</a></li>
</ul></li>
@endif
