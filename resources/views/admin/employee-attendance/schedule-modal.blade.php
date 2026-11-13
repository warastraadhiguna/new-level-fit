<div class="modal fade" id="createSchedule" tabindex="-1" aria-labelledby="createScheduleLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content">
<form method="POST" action="{{ route('employee-schedules.store') }}">@csrf
<input type="hidden" name="_schedule_form" value="1">
<div class="modal-header"><h5 class="modal-title" id="createScheduleLabel">Create Schedule</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body">
<p>Pilih pegawai, tanggal, hari, dan shift. Satu pegawai hanya boleh memiliki satu jadwal per tanggal.</p>
@if($errors->any() && old('_schedule_form'))<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="row g-3">
<div class="col-md-4"><label>Pegawai (bisa pilih beberapa)</label><select id="shiftEmployees" name="employee_ids[]" multiple size="7" required class="form-control">@foreach($employees as $employee)<option value="{{ $employee->id }}" {{ in_array($employee->id,old('employee_ids',[])) ? 'selected':'' }}>{{ $employee->full_name }} · {{ $employee->employee_code }}{{ $employee->all_branch ? ' · Mobile':'' }}</option>@endforeach</select><div class="d-flex gap-2 mt-2"><button type="button" class="btn btn-sm btn-outline-primary" onclick="Array.from(document.getElementById('shiftEmployees').options).forEach(option=>option.selected=true)">Pilih semua</button><button type="button" class="btn btn-sm btn-outline-secondary" onclick="Array.from(document.getElementById('shiftEmployees').options).forEach(option=>option.selected=false)">Kosongkan</button></div><small>Gunakan Ctrl/Cmd untuk memilih beberapa pegawai.</small></div>
<div class="col-md-8"><div class="row g-3">
<div class="col-md-6"><label>Tanggal mulai</label><input type="date" name="start_date" class="form-control" required value="{{ old('start_date',$from) }}"></div>
<div class="col-md-6"><label>Tanggal akhir</label><input type="date" name="end_date" class="form-control" required value="{{ old('end_date',$to) }}"></div>
<div class="col-md-6"><label>Jenis jadwal</label><select name="is_day_off" class="form-control"><option value="0">Bekerja</option><option value="1" {{ old('is_day_off') ? 'selected':'' }}>Libur</option></select></div>
<div class="col-md-6"><label>Template (untuk jadwal bekerja)</label><select name="template_id" class="form-control"><option value="">Pilih shift</option>@foreach($templates as $template)<option value="{{ $template->id }}" {{ (string)old('template_id')===(string)$template->id ? 'selected':'' }}>{{ $template->name }} · {{ substr($template->start_time,0,5) }}–{{ substr($template->end_time,0,5) }}</option>@endforeach</select></div>
<div class="col-12"><label>Hari yang dijadwalkan</label><div class="d-flex flex-wrap gap-3">@foreach([1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'] as $number=>$day)<label><input type="checkbox" name="weekdays[]" value="{{ $number }}" {{ in_array($number,old('weekdays',[1,2,3,4,5,6,7])) ? 'checked':'' }}> {{ $day }}</label>@endforeach</div></div>
<div class="col-12"><label>Catatan</label><input name="note" class="form-control" maxlength="2000" value="{{ old('note') }}"></div>
</div></div></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan Jadwal</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
</form>
</div></div></div>
