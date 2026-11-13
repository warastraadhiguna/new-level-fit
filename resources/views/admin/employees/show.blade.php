<div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-between">
        <div><h4>Detail Employee</h4><p class="text-muted mb-0">{{ $employee->full_name }} · {{ $employee->employee_code }}</p></div>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-primary">Kembali ke Employees</a>
    </div>
    <div class="card"><div class="card-body">
        <div class="row g-4">
            <div class="col-md-3">
                @if($employee->photo_path)<img src="{{ route('employees.photo',[$employee->id,'photo']) }}" class="img-fluid rounded" alt="Foto {{ $employee->full_name }}">@else<p class="text-muted">Belum ada foto pegawai.</p>@endif
            </div>
            <div class="col-md-9"><dl class="row">
                @foreach([
                    'Kode'=>$employee->employee_code,'Nama'=>$employee->full_name,
                    'Tanggal lahir'=>$employee->born ? $employee->born->format('d M Y') : '-',
                    'Jabatan'=>\App\Models\Employee::POSITION_CATEGORIES[$employee->position_category] ?? '-',
                    'Telepon'=>$employee->phone ?: '-',
                    'Mulai kerja'=>$employee->employment_start_date ? $employee->employment_start_date->format('d M Y') : '-',
                    'Resign'=>$employee->resign_date ? $employee->resign_date->format('d M Y') : '-',
                    'Status'=>$employee->is_active ? 'Aktif':'Nonaktif',
                    'Akses cabang'=>$employee->all_branch ? 'Semua cabang':'Cabang asal',
                    'Note'=>$employee->note ?: '-',
                ] as $label=>$value)<dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8" style="white-space:pre-wrap">{{ $value }}</dd>@endforeach
            </dl>
            @if($employee->ktp_photo_path)<a class="btn btn-outline-secondary mb-3" href="{{ route('employees.photo',[$employee->id,'ktp']) }}" target="_blank" rel="noopener">Lihat Foto KTP</a>@endif
            @if($employee->is_active && app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'face-enrollment.index'))
                <div class="border-top pt-3"><h5>Pendaftaran Wajah</h5><p>Daftarkan atau kelola contoh wajah {{ $employee->full_name }} untuk absensi.</p>
                <a class="btn btn-primary" href="{{ route('face-enrollment.index',['employee_id'=>$employee->id]) }}">Pendaftaran Wajah</a></div>
            @endif
            </div>
        </div>
    </div></div>
</div>
