<div class="col-xl-12">
<div class="page-title flex-wrap justify-content-between"><h4>Schedules</h4>
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-schedules.store'))<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSchedule">+ Create Schedule</button>@endif
</div>
@include('admin.employee-attendance.navigation')
<div class="card"><div class="card-body">@include('admin.employee-attendance.schedule-filter')
<p class="text-muted">Untuk mengganti jadwal, hapus jadwal lama lalu buat ulang. Jadwal yang sudah memiliki absensi dikunci.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Pegawai</th><th>Jabatan</th><th>Shift</th><th>Mulai</th><th>Selesai</th><th>Toleransi</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
@forelse($schedules as $schedule)<tr><td>{{ $schedule->work_date }}</td><td>{{ $schedule->full_name }}<small class="d-block">{{ $schedule->employee_code }}</small></td><td>{{ optional($positionCategories->get($schedule->position_category))->name ?? ($schedule->position_category ?: '-') }}</td><td>{{ $schedule->shift_name }}</td><td>{{ $schedule->starts_at ?? '-' }}</td><td>{{ $schedule->ends_at ?? '-' }}</td><td>{{ $schedule->grace_minutes }} menit</td><td>{{ $schedule->note }}</td><td>
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-schedules.destroy'))<form method="POST" action="{{ route('employee-schedules.destroy',$schedule->id) }}" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>@endif
</td></tr>@empty<tr><td colspan="9">Belum ada jadwal pada periode ini.</td></tr>@endforelse
</tbody></table></div>{{ $schedules->links() }}</div></div></div>

@if($attendanceAccess->allowsRoute(Auth::user(),'employee-schedules.store'))
    @include('admin.employee-attendance.schedule-modal')
    @if($errors->any() && old('_schedule_form'))
    <script>document.addEventListener('DOMContentLoaded',function(){new bootstrap.Modal(document.getElementById('createSchedule')).show();});</script>
    @endif
@endif
