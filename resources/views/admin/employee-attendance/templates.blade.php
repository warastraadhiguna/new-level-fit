<div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-between">
        <h4>Shift template</h4>
        @if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.store'))
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShiftTemplate">+ Tambah Template Shift</button>
        @endif
    </div>
    @include('admin.employee-attendance.navigation')
    <div class="card"><div class="card-body"><div class="table-responsive">
        <table class="table">
            <thead><tr><th>No</th><th>Nama Shift</th><th>Jam Mulai</th><th>Jam Selesai</th><th>Toleransi</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($templates as $template)
                <tr>
                    <td>{{ $loop->iteration }}</td><td>{{ $template->name }}</td>
                    <td>{{ substr($template->start_time,0,5) }}</td><td>{{ substr($template->end_time,0,5) }}@if($template->end_time<$template->start_time)<small class="d-block text-muted">Hari berikutnya</small>@endif</td>
                    <td>{{ $template->grace_minutes }} menit</td>
                    <td><span class="badge {{ $template->is_active ? 'badge-success':'badge-secondary' }}">{{ $template->is_active ? 'Aktif':'Nonaktif' }}</span></td>
                    <td>
                        @if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.update'))
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editShiftTemplate{{ $template->id }}">Ubah</button>
                        @endif
                        @if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.destroy'))
                            <form class="d-inline" method="POST" action="{{ route('employee-shifts.destroy',$template->id) }}" onsubmit="return confirm('Hapus template shift ini?')">
                                @csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty<tr><td colspan="7" class="text-center">Belum ada template shift.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.store'))
    @include('admin.employee-attendance.template-modal',['template'=>null,'formId'=>'addShiftTemplate'])
@endif
@if($attendanceAccess->allowsRoute(Auth::user(),'employee-shifts.update'))
    @foreach($templates as $template)
        @include('admin.employee-attendance.template-modal',['template'=>$template,'formId'=>'editShiftTemplate'.$template->id])
    @endforeach
@endif
@if($errors->any() && old('_template_form'))
<script>
    document.addEventListener('DOMContentLoaded',function(){
        const modal=document.getElementById(@json(old('_template_id') ? 'editShiftTemplate'.old('_template_id') : 'addShiftTemplate'));
        if(modal)new bootstrap.Modal(modal).show();
    });
</script>
@endif
