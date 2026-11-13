<div class="col-xl-12">
<h4>Shift template</h4>@include('admin.employee-attendance.navigation')
<p>Contoh: Shift Pagi 06:00–13:00 dan Shift Siang 13:00–19:00. Jam selesai yang lebih kecil dari jam mulai dihitung sebagai hari berikutnya. Toleransi dikurangi dari perhitungan menit terlambat.</p>
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.store'))
<div class="card"><div class="card-body"><h5>Tambah template</h5><form method="POST" action="{{ route('employee-shifts.store') }}">@csrf @include('admin.employee-attendance.template-fields',['template'=>null])</form></div></div>
@endif
@forelse($templates as $template)
<div class="card"><div class="card-body">
<h5>{{ $template->name }} <small class="text-muted">{{ $template->is_active ? 'Aktif':'Nonaktif' }}</small></h5>
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.update'))
<form method="POST" action="{{ route('employee-shifts.update',$template->id) }}">@csrf @method('PUT') @include('admin.employee-attendance.template-fields')</form>
@else<p>{{ substr($template->start_time,0,5) }}–{{ substr($template->end_time,0,5) }} · Toleransi {{ $template->grace_minutes }} menit</p>@endif
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.destroy'))<form method="POST" action="{{ route('employee-shifts.destroy',$template->id) }}" class="mt-3" onsubmit="return confirm('Hapus template ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus template</button></form>@endif
</div></div>
@empty<div class="alert alert-info">Belum ada template shift. Buat template pagi dan siang terlebih dahulu.</div>@endforelse
</div>
