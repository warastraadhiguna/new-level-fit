<div class="modal fade" id="{{ $formId }}" tabindex="-1" aria-labelledby="{{ $formId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <form method="POST" enctype="multipart/form-data" action="{{ $formEmployee ? route('employees.update',$formEmployee->id) : route('employees.store') }}">
            @csrf
            @if($formEmployee) @method('PUT') @endif
            <input type="hidden" name="_employee_id" value="{{ $formEmployee ? $formEmployee->id : '' }}">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $formId }}Label">{{ $formEmployee ? 'Ubah Pegawai' : 'Tambah Pegawai' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($restoreInput && $errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @include('admin.employees.fields')
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">{{ $formEmployee ? 'Simpan Perubahan':'Simpan Pegawai' }}</button>
                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </form>
    </div></div>
</div>
