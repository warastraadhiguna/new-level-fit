<div class="col-lg-8"><div class="card"><div class="card-body"><h3>Ubah Akun HRD</h3>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('hrd-accounts.update',$account->id) }}">@csrf @method('PUT') @include('admin.role-accounts.fields')<button class="btn btn-primary">Simpan Perubahan</button><a class="btn btn-light" href="{{ route('hrd-accounts.index') }}">Kembali</a></form>
</div></div></div>
