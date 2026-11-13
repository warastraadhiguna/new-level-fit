@foreach(['employee-shifts.index'=>'Template Shift','employee-schedules.index'=>'Jadwal Pegawai','employee-leaves.index'=>'Izin & Cuti','employee-attendance.report'=>'Rekap Kehadiran'] as $attendanceRoute=>$attendanceLabel)
@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),$attendanceRoute))<li><a href="{{ route($attendanceRoute) }}">{{ $attendanceLabel }}</a></li>@endif
@endforeach
