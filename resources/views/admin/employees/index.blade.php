<div class="col-12">
<div class="card"><div class="card-body"><h3>Data Pegawai</h3><p>Cabang: {{ optional(Auth::user()->branchStore)->name }}. Data pegawai absensi terpisah dari akun user dan trainer.</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<details @if($errors->any()) open @endif><summary class="mb-3">Tambah Pegawai</summary><form method="POST" action="{{ route('employees.store') }}">@csrf @include('admin.employees.fields')<button class="btn btn-primary">Simpan Pegawai</button></form></details>
</div></div>
<div class="card"><div class="card-body">
<form method="GET" class="d-flex flex-wrap mb-3" style="gap:10px"><input name="search" class="form-control" style="max-width:280px" placeholder="Cari nama atau kode" value="{{ request('search') }}"><select name="per_page" class="form-control" style="max-width:100px">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" {{ (int)request('per_page',10)===$size ? 'selected':'' }}>{{ $size }}</option>@endforeach</select><button class="btn btn-primary">Cari</button></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Jabatan</th><th>Telepon</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($employees as $employee)<tr><td>{{ $employee->employee_code }}</td><td>{{ $employee->full_name }}</td><td>{{ $employee->position ?: '-' }}</td><td>{{ $employee->phone ?: '-' }}</td><td>{{ $employee->is_active ? 'Aktif':'Nonaktif' }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('employees.edit',$employee->id) }}">Ubah</a><form class="d-inline" method="POST" action="{{ route('employees.destroy',$employee->id) }}" onsubmit="return confirm('Hapus sementara pegawai ini? Pegawai tidak dapat absen setelah dihapus.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus Sementara</button></form></td></tr>
@empty<tr><td colspan="6">Belum ada data pegawai.</td></tr>@endforelse
</tbody></table></div>{{ $employees->links() }}
</div></div></div>
