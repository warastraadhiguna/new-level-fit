@php($attendanceAccess=app(\App\Support\RoleFeatureAccess::class))
<div class="d-flex flex-wrap gap-2 mb-3">
@foreach(['employee-shifts.index'=>'Template Shift','employee-schedules.index'=>'Jadwal Pegawai','employee-leaves.index'=>'Izin & Cuti','employee-attendance.report'=>'Rekap Kehadiran'] as $navRoute=>$navLabel)
@if($attendanceAccess->allowsRoute(Auth::user(),$navRoute))<a class="btn {{ request()->routeIs($navRoute) ? 'btn-primary':'btn-outline-primary' }}" href="{{ route($navRoute) }}">{{ $navLabel }}</a>@endif
@endforeach
</div>
<p class="text-muted">Cabang: {{ optional(Auth::user()->branchStore)->name }} · Zona waktu: {{ config('face.attendance_timezone') }}</p>
@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
