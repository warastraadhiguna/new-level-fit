<div class="col-xl-12"><h4>Schedules</h4>@include('admin.employee-attendance.navigation')
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-schedules.store'))
<div class="card"><div class="card-body"><h5>Atur jadwal sekaligus</h5>
<p>Pilih beberapa pegawai dan hari. Satu pegawai hanya boleh memiliki satu jadwal per tanggal, termasuk pegawai mobile lintas cabang.</p>
<form method="POST" action="{{ route('employee-schedules.store') }}">@csrf
<div class="row g-3">
<div class="col-md-4"><label>Pegawai (bisa pilih beberapa)</label><select id="shiftEmployees" name="employee_ids[]" multiple size="7" required class="form-control">@foreach($employees as $employee)<option value="{{ $employee->id }}" {{ in_array($employee->id,old('employee_ids',[])) ? 'selected':'' }}>{{ $employee->full_name }} · {{ $employee->employee_code }}{{ $employee->all_branch ? ' · Mobile':'' }}</option>@endforeach</select><div class="d-flex gap-2 mt-2"><button type="button" class="btn btn-sm btn-outline-primary" onclick="Array.from(document.getElementById('shiftEmployees').options).forEach(option=>option.selected=true)">Pilih semua</button><button type="button" class="btn btn-sm btn-outline-secondary" onclick="Array.from(document.getElementById('shiftEmployees').options).forEach(option=>option.selected=false)">Kosongkan</button></div><small>Gunakan Ctrl/Cmd untuk memilih beberapa pegawai.</small></div>
<div class="col-md-8"><div class="row g-3">
<div class="col-md-6"><label>Tanggal mulai</label><input type="date" name="start_date" class="form-control" required value="{{ old('start_date',$from) }}"></div>
<div class="col-md-6"><label>Tanggal akhir</label><input type="date" name="end_date" class="form-control" required value="{{ old('end_date',$to) }}"></div>
<div class="col-md-6"><label>Jenis jadwal</label><select name="is_day_off" class="form-control"><option value="0">Bekerja</option><option value="1" {{ old('is_day_off') ? 'selected':'' }}>Libur</option></select></div>
<div class="col-md-6"><label>Template (untuk jadwal bekerja)</label><select name="template_id" class="form-control"><option value="">Pilih shift</option>@foreach($templates as $template)<option value="{{ $template->id }}" {{ (string)old('template_id')===(string)$template->id ? 'selected':'' }}>{{ $template->name }} · {{ substr($template->start_time,0,5) }}–{{ substr($template->end_time,0,5) }}</option>@endforeach</select></div>
<div class="col-12"><label>Hari yang dijadwalkan</label><div class="d-flex flex-wrap gap-3">@foreach([1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'] as $number=>$day)<label><input type="checkbox" name="weekdays[]" value="{{ $number }}" {{ in_array($number,old('weekdays',[1,2,3,4,5,6,7])) ? 'checked':'' }}> {{ $day }}</label>@endforeach</div></div>
<div class="col-12"><label>Catatan</label><input name="note" class="form-control" maxlength="2000" value="{{ old('note') }}"></div>
</div></div></div><button class="btn btn-primary mt-3">Simpan Jadwal</button>
</form></div></div>@endif
<div class="card"><div class="card-body">@include('admin.employee-attendance.filter')
<p class="text-muted">Untuk mengganti jadwal, hapus jadwal lama lalu buat ulang. Jadwal yang sudah memiliki absensi dikunci.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Pegawai</th><th>Shift</th><th>Mulai</th><th>Selesai</th><th>Toleransi</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
@forelse($schedules as $schedule)<tr><td>{{ $schedule->work_date }}</td><td>{{ $schedule->full_name }}<small class="d-block">{{ $schedule->employee_code }}</small></td><td>{{ $schedule->shift_name }}</td><td>{{ $schedule->starts_at ?? '-' }}</td><td>{{ $schedule->ends_at ?? '-' }}</td><td>{{ $schedule->grace_minutes }} menit</td><td>{{ $schedule->note }}</td><td>
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-schedules.destroy'))<form method="POST" action="{{ route('employee-schedules.destroy',$schedule->id) }}" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>@endif
</td></tr>@empty<tr><td colspan="8">Belum ada jadwal pada periode ini.</td></tr>@endforelse
</tbody></table></div>{{ $schedules->links() }}</div></div></div>
