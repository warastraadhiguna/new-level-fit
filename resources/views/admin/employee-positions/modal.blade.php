@php
    $restoreCategoryInput=$errors->any() && (string)old('_category_id')===(string)optional($formCategory)->id;
@endphp
<div class="modal fade" id="{{ $formId }}" tabindex="-1" aria-labelledby="{{ $formId }}Label" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="{{ $formCategory ? route('employee-positions.update',$formCategory->id) : route('employee-positions.store') }}">
        @csrf @if($formCategory) @method('PUT') @endif
        <input type="hidden" name="_category_id" value="{{ optional($formCategory)->id }}">
        <div class="modal-header"><h5 id="{{ $formId }}Label" class="modal-title">{{ $formCategory ? 'Ubah':'Tambah' }} Jabatan</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label" for="{{ $formId }}Code">Kode</label>
            @if($formCategory)<input class="form-control" id="{{ $formId }}Code" value="{{ $formCategory->code }}" readonly><small class="text-muted">Kode tetap agar data jabatan pegawai terjaga.</small>
            @else<input class="form-control" id="{{ $formId }}Code" name="code" required maxlength="20" pattern="[A-Za-z0-9_]+" value="{{ $restoreCategoryInput ? old('code') : '' }}" placeholder="Contoh: SECURITY"><small class="text-muted">Huruf, angka, dan garis bawah, maksimal 20 karakter.</small>@endif</div>
            <div class="mb-3"><label class="form-label" for="{{ $formId }}Name">Nama jabatan</label><input class="form-control" id="{{ $formId }}Name" name="name" required maxlength="100" value="{{ $restoreCategoryInput ? old('name') : optional($formCategory)->name }}"></div>
            <div class="mb-3"><label class="form-label" for="{{ $formId }}Active">Status</label><select class="form-control" id="{{ $formId }}Active" name="is_active">
                @foreach([1=>'Aktif',0=>'Nonaktif'] as $value=>$label)<option value="{{ $value }}" {{ (int)($restoreCategoryInput ? old('is_active',1) : ($formCategory ? $formCategory->is_active : 1))===$value ? 'selected':'' }}>{{ $label }}</option>@endforeach
            </select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Simpan</button><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Tutup</button></div>
    </form>
</div></div></div>
