<div class="col-lg-8"><div class="card"><div class="card-body"><h3>Ubah Pegawai</h3><p>Cabang: {{ optional(Auth::user()->branchStore)->name }}</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('employees.update',$employee->id) }}">@csrf @method('PUT') @include('admin.employees.fields')<button class="btn btn-primary">Simpan Perubahan</button><a href="{{ route('employees.index') }}" class="btn btn-light">Kembali</a></form>
</div></div></div>
