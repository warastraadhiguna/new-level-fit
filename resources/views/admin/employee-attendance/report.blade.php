<div class="col-xl-12"><h4>Attendance Report</h4>@include('admin.employee-attendance.navigation')
<div class="card"><div class="card-body">@include('admin.employee-attendance.filter')
<div class="d-flex flex-wrap gap-3 mb-3">@foreach(['hadir'=>'Hadir','terlambat'=>'Terlambat','absen'=>'Tidak hadir','izin'=>'Izin/Cuti/Sakit','late'=>'Menit terlambat','early'=>'Menit pulang awal','work'=>'Menit bekerja'] as $key=>$label)<div class="border rounded p-3"><small>{{ $label }}</small><strong class="d-block">{{ $summary[$key] }}</strong></div>@endforeach</div>
<p class="text-muted">Tidak hadir dihitung setelah jam akhir shift. Tanpa jadwal tidak dihitung mangkir. Durasi bekerja adalah selisih masuk/pulang, termasuk waktu istirahat. Shift malam menggunakan tanggal mulai shift.</p>
<a class="btn btn-outline-primary mb-3" href="{{ route('employee-attendance.report.export',array_merge(request()->except('page'),['from'=>$from,'to'=>$to])) }}">Unduh CSV</a>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Pegawai</th><th>Shift / Jam</th><th>Masuk</th><th>Pulang</th><th>Terlambat</th><th>Pulang awal</th><th>Durasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['name'] }}<small class="d-block">{{ $row['code'] }}</small></td><td>{{ $row['shift'] }}<small class="d-block">{{ $row['start'] }}{{ $row['end'] ? ' — '.$row['end']:'' }}</small></td><td>{{ $row['check_in'] ?? '-' }}</td><td>{{ $row['check_out'] ?? '-' }}</td><td>{{ $row['late'] }} menit</td><td>{{ $row['early'] }} menit</td><td>{{ $row['work_minutes']!==null ? $row['work_minutes'].' menit':'-' }}</td><td>{{ $row['status'] }}</td><td>
@if($row['record_ids'] && $attendanceAccess->allowsRoute(Auth::user(),'employee-attendance.destroy'))
<form method="POST" action="{{ route('employee-attendance.destroy',[$row['employee_id'],$row['date']]) }}" onsubmit="return confirm('Hapus seluruh absensi masuk dan pulang pegawai ini pada tanggal kerja tersebut? Tindakan ini tidak dapat dibatalkan. Jadwal tetap tersimpan.')">
@csrf @method('DELETE')
@foreach($row['record_ids'] as $recordId)<input type="hidden" name="record_ids[]" value="{{ $recordId }}">@endforeach
<input type="hidden" name="from" value="{{ $from }}"><input type="hidden" name="to" value="{{ $to }}">
<input type="hidden" name="employee_id" value="{{ request('employee_id') }}"><input type="hidden" name="status" value="{{ request('status') }}"><input type="hidden" name="page" value="{{ $rows->currentPage() }}">
<button class="btn btn-sm btn-outline-danger" type="submit">Hapus Attendance</button>
</form>
@endif
</td></tr>@empty<tr><td colspan="10">Tidak ada data sesuai filter.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}</div></div></div>
