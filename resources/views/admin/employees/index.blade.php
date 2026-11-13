<div class="col-xl-12">
    @include('admin.employees.birthdays')
    <div class="page-title flex-wrap justify-content-between">
        <div><h4>Data Pegawai</h4><p class="text-muted mb-0">Cabang: {{ optional(Auth::user()->branchStore)->name }}</p></div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddEmployee">+ Tambah Pegawai</button>
    </div>
    <div class="card"><div class="card-body">
        <form id="employeeSearchForm" method="GET" action="{{ route('employees.index') }}" class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <label for="employeePageSize" class="text-muted mb-0">Show</label>
                <select id="employeePageSize" name="per_page" class="form-control" style="width:90px">
                    @foreach([10,25,50,100] as $size)<option value="{{ $size }}" {{ (int)request('per_page',10)===$size ? 'selected':'' }}>{{ $size }}</option>@endforeach
                </select><span class="text-muted">entries</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <label for="employeeSearch" class="text-muted mb-0">Search:</label>
                <input id="employeeSearch" name="search" class="form-control" style="max-width:280px" placeholder="Cari nama atau kode" value="{{ request('search') }}">
                @if(request('search'))<a href="{{ route('employees.index') }}" class="btn btn-danger light">Reset Search</a>@endif
            </div>
        </form>
        <div class="table-responsive full-data">
            <table class="table table-responsive-lg display dataTablesCard student-tab dataTable no-footer">
                <thead><tr><th>No</th><th>Foto</th><th>Kode</th><th>Nama</th><th>Tanggal lahir</th><th>Jabatan</th><th>Masa kerja</th><th>Telepon</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>{{ $employees->firstItem() + $loop->index }}</td>
                            <td>@if($employee->photo_path)<img src="{{ route('employees.photo',[$employee->id,'photo']) }}" alt="Foto {{ $employee->full_name }}" class="rounded" width="70" loading="lazy">@else - @endif</td>
                            <td>{{ $employee->employee_code }}</td><td>{{ $employee->full_name }}@if($employee->all_branch)<span class="badge badge-info d-block">Semua cabang</span>@endif @if($employee->note)<small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($employee->note,80) }}</small>@endif</td>
                            <td>{{ $employee->born ? $employee->born->format('d M Y') : '-' }}</td>
                            <td>@if($employee->position_category)<span class="badge badge-primary">{{ \App\Models\Employee::POSITION_CATEGORIES[$employee->position_category] ?? $employee->position_category }}</span>@endif<div>{{ $employee->position ?: '-' }}</div></td>
                            <td class="text-nowrap"><small>Mulai:</small> {{ $employee->employment_start_date ? $employee->employment_start_date->format('d M Y') : '-' }}<br><small>Resign:</small> {{ $employee->resign_date ? $employee->resign_date->format('d M Y') : '-' }}</td>
                            <td>{{ $employee->phone ?: '-' }}</td>
                            <td><span class="badge {{ $employee->is_active ? 'badge-success':'badge-secondary' }}">{{ $employee->is_active ? 'Aktif':'Nonaktif' }}</span></td>
                            <td>
                                <button type="button" class="btn light btn-warning btn-xs mb-1" data-bs-toggle="modal" data-bs-target="#modalEditEmployee{{ $employee->id }}">Ubah</button>
                                <form class="d-inline" method="POST" action="{{ route('employees.destroy',$employee->id) }}" onsubmit="return confirm('Hapus sementara pegawai ini? Pegawai tidak dapat absen setelah dihapus.')">
                                    @csrf @method('DELETE')<button class="btn light btn-danger btn-xs">Hapus Sementara</button>
                                </form>
                            </td>
                        </tr>
                    @empty<tr><td colspan="10" class="text-center">Belum ada data pegawai.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
            <div class="text-muted mb-2">Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} data</div>
            {{ $employees->links('pagination::bootstrap-4') }}
        </div>
    </div></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('employeeSearchForm');
        const input = document.getElementById('employeeSearch');
        const pageSize = document.getElementById('employeePageSize');
        const initialSearch = input.value;
        let timer;
        let composing = false;
        let submitted = false;

        function submitSearch() {
            clearTimeout(timer);
            if (submitted) return;
            submitted = true;
            form.submit();
        }

        function scheduleSearch() {
            clearTimeout(timer);
            if (composing || input.value === initialSearch) return;
            timer = setTimeout(submitSearch, 1000);
        }

        input.addEventListener('input', scheduleSearch);
        input.addEventListener('compositionstart', function () {
            composing = true;
            clearTimeout(timer);
        });
        input.addEventListener('compositionend', function () {
            composing = false;
            scheduleSearch();
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.isComposing && !composing) {
                event.preventDefault();
                submitSearch();
            }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitSearch();
        });
        pageSize.addEventListener('change', submitSearch);
    });
</script>

@php
    $modalEmployees = $employees->getCollection();
    if ($editingEmployee && !$modalEmployees->contains('id', $editingEmployee->id)) $modalEmployees = $modalEmployees->concat([$editingEmployee]);
    $openModal = $errors->any() && !old('_employee_id') ? 'modalAddEmployee' : ($editingEmployee ? 'modalEditEmployee'.$editingEmployee->id : null);
@endphp
@include('admin.employees.modal', ['formEmployee'=>null, 'formId'=>'modalAddEmployee', 'restoreInput'=>$errors->any() && !old('_employee_id')])
@foreach($modalEmployees as $modalEmployee)
    @include('admin.employees.modal', ['formEmployee'=>$modalEmployee, 'formId'=>'modalEditEmployee'.$modalEmployee->id, 'restoreInput'=>$errors->any() && (string)old('_employee_id')===(string)$modalEmployee->id])
@endforeach
@if($openModal)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById(@json($openModal))).show();
        });
    </script>
@endif
