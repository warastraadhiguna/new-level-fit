@php
    $canSchedule=$employee->is_active && $attendanceAccess->allowsRoute(Auth::user(),'employee-schedules.calendar.store');
    $canLeave=$employee->is_active && $attendanceAccess->allowsRoute(Auth::user(),'employee-leaves.calendar.store');
    $canApply=$canSchedule || $canLeave;
    $oldDates=old('_calendar_form') ? old('dates',[]) : [];
    $leading=$monthStart->isoWeekday()-1;
    $trailing=(7-(($leading+count($days))%7))%7;
    $monthLabel=$monthStart->copy()->locale('id')->translatedFormat('F Y');
    $leaveLabels=['izin'=>'Izin','cuti'=>'Cuti','sakit'=>'Sakit'];
@endphp
<link href="{{ asset('css/employee-calendar.css') }}" rel="stylesheet">
<div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-between">
        <div><h4>Schedule Employee</h4><p class="mb-0">{{ $employee->full_name }} · {{ $employee->employee_code }}</p></div>
        @if($attendanceAccess->allowsRoute(Auth::user(),'employees.index'))<a class="btn btn-outline-primary" href="{{ route('employees.index') }}">Kembali ke Employees</a>@endif
    </div>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="card"><div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between mb-4">
            <div><h4>{{ $monthLabel }}</h4><p class="text-muted mb-0">{{ $canApply ? 'Pilih tanggal dengan mencentang kalender, lalu pilih aksi.' : 'Jadwal pegawai pada bulan yang dipilih.' }}</p></div>
            <form method="GET" class="d-flex gap-2 align-items-end"><div><label class="form-label" for="calendarMonth">Bulan</label><input type="month" id="calendarMonth" name="month" value="{{ $month }}" required class="form-control"></div><button class="btn btn-primary">Tampilkan</button></form>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('employee-schedules.calendar',[$employee->id,'month'=>$monthStart->copy()->subMonth()->format('Y-m')]) }}">← Sebelumnya</a><a class="btn btn-sm btn-outline-primary" href="{{ route('employee-schedules.calendar',[$employee->id,'month'=>$monthStart->copy()->addMonth()->format('Y-m')]) }}">Berikutnya →</a></div>
            @if($canApply)<div class="d-flex flex-wrap gap-2 align-items-center"><span id="calendarSelectionCount" aria-live="polite">0 tanggal dipilih</span><button type="button" id="calendarSelectAll" class="btn btn-sm btn-outline-primary">Pilih semua</button><button type="button" id="calendarClear" class="btn btn-sm btn-outline-secondary">Kosongkan</button><button type="button" id="calendarOpenAction" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#calendarActionModal" disabled>Pilih Aksi</button></div>@endif
        </div>
        <div class="employee-calendar">
            @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $weekday)<div class="calendar-weekday">{{ $weekday }}</div>@endforeach
            @for($blank=0;$blank<$leading;$blank++)<div class="calendar-blank" aria-hidden="true"></div>@endfor
            @foreach($days as $day)
            <label class="calendar-day {{ $day['locked'] ? 'is-locked':'' }}" for="calendarDay{{ $day['day'] }}">
                <span class="calendar-day-top"><strong>{{ $day['day'] }}</strong>
                    <input type="checkbox" id="calendarDay{{ $day['day'] }}" class="calendar-date-choice" name="dates[]" value="{{ $day['date'] }}" form="employeeCalendarForm" aria-label="Pilih tanggal {{ $day['date'] }}" {{ in_array($day['date'],$oldDates,true) ? 'checked':'' }} @if(!$canApply || $day['locked']) disabled @endif>
                </span>
                @if($day['schedule'])<span class="calendar-shift {{ $day['schedule']->is_day_off ? 'is-off':'is-work' }}">{{ $day['schedule']->shift_name }}</span>@if(!$day['schedule']->is_day_off)<small>{{ substr($day['schedule']->starts_at,11,5) }}–{{ substr($day['schedule']->ends_at,11,5) }}</small>@endif @else<small class="text-muted">Belum dijadwalkan</small>@endif
                @if($day['leave'])<span class="calendar-leave">{{ $leaveLabels[$day['leave']->type] ?? $day['leave']->type }} · {{ $day['leave']->status==='approved' ? 'Disetujui':'Menunggu' }}</span>@endif
                @if($day['recorded'])<small class="text-muted">Sudah absen · dikunci</small>@elseif($day['foreign'])<small class="text-muted">{{ $day['schedule']->branch_name }} · dikunci</small>@endif
            </label>
            @endforeach
            @for($blank=0;$blank<$trailing;$blank++)<div class="calendar-blank" aria-hidden="true"></div>@endfor
        </div>
        <p class="small text-muted mt-3 mb-0">Tanggal yang sudah memiliki absensi, izin/cuti aktif, atau jadwal cabang lain dikunci. Penggantian jadwal memerlukan izin Create dan Delete pada Jadwal Shift Pegawai. Izin/cuti baru menunggu persetujuan di Day Off.</p>
    </div></div>
    <div class="card"><div class="card-body"><h5>Jadwal {{ $monthLabel }}</h5><div class="table-responsive"><table class="table">
        <thead><tr><th>Tanggal</th><th>Shift</th><th>Jam kerja</th><th>Cabang</th><th>Izin/Cuti</th><th>Catatan</th></tr></thead><tbody>
        @forelse(collect($days)->filter(fn($day)=>$day['schedule'] || $day['leave'] || $day['recorded']) as $day)
        <tr><td>{{ $day['date'] }}</td><td>{{ $day['schedule']->shift_name ?? '-' }}@if($day['recorded'])<small class="d-block text-muted">Sudah absen</small>@endif</td><td>{{ $day['schedule']->starts_at ?? '-' }}<br>{{ $day['schedule']->ends_at ?? '' }}</td><td>{{ $day['schedule']->branch_name ?? '-' }}</td><td>@if($day['leave']){{ $leaveLabels[$day['leave']->type] ?? $day['leave']->type }}<small class="d-block">{{ $day['leave']->status==='approved' ? 'Disetujui':'Menunggu persetujuan' }}</small>@else - @endif</td><td>{{ $day['schedule']->note ?? ($day['leave']->reason ?? '-') }}</td></tr>
        @empty<tr><td colspan="6">Belum ada jadwal atau pengajuan pada bulan ini.</td></tr>@endforelse
        </tbody></table></div></div></div>
</div>
@if($canApply)
<div class="modal fade" id="calendarActionModal" tabindex="-1" aria-labelledby="calendarActionLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<form method="POST" id="employeeCalendarForm" action="{{ $canSchedule ? route('employee-schedules.calendar.store',$employee->id) : route('employee-leaves.calendar.store',$employee->id) }}">
    @csrf <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="_calendar_form" value="1">
    <div class="modal-header"><h5 class="modal-title" id="calendarActionLabel">Atur tanggal pilihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body">
        <p id="calendarSelectedDates" class="small"></p>
        <div class="mb-3"><label class="form-label" for="calendarOperation">Aksi</label><select class="form-control" name="operation" id="calendarOperation">
            @foreach(($canSchedule ? ['shift'=>'Tambah / ganti shift','libur'=>'Libur'] : []) + ($canLeave ? ['izin'=>'Ajukan izin','cuti'=>'Ajukan cuti','sakit'=>'Ajukan sakit'] : []) as $value=>$label)<option value="{{ $value }}" {{ old('operation')===$value ? 'selected':'' }}>{{ $label }}</option>@endforeach
        </select></div>
        <div class="mb-3" id="calendarTemplateField"><label class="form-label" for="calendarTemplate">Template shift</label><select class="form-control" name="template_id" id="calendarTemplate"><option value="">Pilih shift</option>@foreach($templates as $template)<option value="{{ $template->id }}" {{ (string)old('template_id')===(string)$template->id ? 'selected':'' }}>{{ $template->name }} · {{ substr($template->start_time,0,5) }}–{{ substr($template->end_time,0,5) }}</option>@endforeach</select></div>
        <div class="mb-3"><label class="form-label" for="calendarNote">Catatan / alasan izin</label><textarea class="form-control" name="note" id="calendarNote" maxlength="2000">{{ old('note') }}</textarea></div>
        <p class="small text-muted" id="calendarActionHint"></p>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-primary" id="calendarSave">Simpan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
</form>
</div></div></div>
<script>
window.employeeCalendarConfig={scheduleUrl:@json($canSchedule ? route('employee-schedules.calendar.store',$employee->id) : null),leaveUrl:@json($canLeave ? route('employee-leaves.calendar.store',$employee->id) : null),reopen:@json($errors->any() && (bool)old('_calendar_form'))};
</script>
<script src="{{ asset('js/employee-calendar.js') }}?v={{ filemtime(public_path('js/employee-calendar.js')) }}" defer></script>
@endif
