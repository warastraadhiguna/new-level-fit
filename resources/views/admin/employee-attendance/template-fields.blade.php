<div class="row g-3">
<div class="col-md-3"><label>Nama shift</label><input name="name" class="form-control" required maxlength="100" value="{{ $template->name ?? old('name') }}" placeholder="Shift Pagi"></div>
<div class="col-md-2"><label>Jam mulai</label><input type="time" name="start_time" class="form-control" required value="{{ isset($template) ? substr($template->start_time,0,5) : old('start_time','06:00') }}"></div>
<div class="col-md-2"><label>Jam selesai</label><input type="time" name="end_time" class="form-control" required value="{{ isset($template) ? substr($template->end_time,0,5) : old('end_time','13:00') }}"></div>
<div class="col-md-2"><label>Toleransi (menit)</label><input type="number" name="grace_minutes" class="form-control" required min="0" max="180" value="{{ $template->grace_minutes ?? old('grace_minutes',0) }}"></div>
<div class="col-md-2"><label>Status</label><select name="is_active" class="form-control"><option value="1">Aktif</option><option value="0" {{ isset($template) && !$template->is_active ? 'selected':'' }}>Nonaktif</option></select></div>
<div class="col-md-1 align-self-end"><button class="btn btn-primary">Simpan</button></div>
</div>
