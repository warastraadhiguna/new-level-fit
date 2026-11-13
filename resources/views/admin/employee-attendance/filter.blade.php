<form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
<div><label>Dari</label><input type="date" name="from" value="{{ $from }}" class="form-control" required></div>
<div><label>Sampai</label><input type="date" name="to" value="{{ $to }}" class="form-control" required></div>
<div><label>Pegawai</label><select name="employee_id" class="form-control"><option value="">Semua pegawai</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" {{ (string)request('employee_id')===(string)$employee->id ? 'selected':'' }}>{{ $employee->full_name }} · {{ $employee->employee_code }}</option>@endforeach</select></div>
@if(request()->routeIs('employee-attendance.report'))<div><label>Status</label><select name="status" class="form-control"><option value="">Semua status</option>@foreach(['Hadir','Terlambat','Belum pulang','Izin','Cuti','Sakit','Libur','Tidak hadir','Terjadwal','Tanpa jadwal'] as $status)<option {{ request('status')===$status ? 'selected':'' }}>{{ $status }}</option>@endforeach</select></div>@endif
<button class="btn btn-primary">Filter</button>
</form>
