@php
    $positionAccess=app(\App\Support\RoleFeatureAccess::class);
@endphp
<div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-between"><h4>Kategori Jabatan</h4>
        @if($positionAccess->allowsRoute(Auth::user(),'employee-positions.store'))<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPositionCategory">+ Tambah Jabatan</button>@endif
    </div>
    <p class="text-muted">Kategori dipakai seluruh cabang pada database attendance yang sama. Kategori nonaktif tidak tersedia untuk pilihan baru, tetapi tetap tersimpan pada pegawai lama.</p>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table">
        <thead><tr><th>Kode</th><th>Nama Jabatan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($categories as $category)<tr><td>{{ $category->code }}</td><td>{{ $category->name }}</td><td><span class="badge {{ $category->is_active ? 'badge-success':'badge-secondary' }}">{{ $category->is_active ? 'Aktif':'Nonaktif' }}</span></td><td>
            @if($positionAccess->allowsRoute(Auth::user(),'employee-positions.update'))<button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editPositionCategory{{ $category->id }}">Ubah</button>@endif
            @if($positionAccess->allowsRoute(Auth::user(),'employee-positions.destroy'))<form class="d-inline" method="POST" action="{{ route('employee-positions.destroy',$category->id) }}" onsubmit="return confirm('Hapus kategori jabatan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>@endif
        </td></tr>@empty<tr><td colspan="4">Belum ada kategori jabatan.</td></tr>@endforelse
        </tbody></table></div></div></div>
</div>
@if($positionAccess->allowsRoute(Auth::user(),'employee-positions.store'))
    @include('admin.employee-positions.modal',['formCategory'=>null,'formId'=>'addPositionCategory'])
@endif
@if($positionAccess->allowsRoute(Auth::user(),'employee-positions.update'))
    @foreach($categories as $category)@include('admin.employee-positions.modal',['formCategory'=>$category,'formId'=>'editPositionCategory'.$category->id])@endforeach
@endif
@if($errors->any())
<script>document.addEventListener('DOMContentLoaded',function(){const modal=document.getElementById(@json(old('_category_id') ? 'editPositionCategory'.old('_category_id') : 'addPositionCategory'));if(modal)new bootstrap.Modal(modal).show();});</script>
@endif
