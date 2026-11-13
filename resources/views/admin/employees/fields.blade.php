@php
    $fieldValue = function ($field, $default = '') use ($formEmployee, $restoreInput) {
        $value = $formEmployee ? $formEmployee->$field : $default;
        if (in_array($field, ['born','employment_start_date','resign_date'], true) && $value) $value = $value->format('Y-m-d');
        if ($field === 'is_active') $value = (int) $value;
        if ($field === 'all_branch') $value = (int) $value;
        return $restoreInput ? old($field, $value) : $value;
    };
@endphp
<div class="row">
    <div class="col-12 mb-3">
        <input type="hidden" name="all_branch" value="0">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="{{ $formId }}AllBranch" name="all_branch" value="1" {{ (int)$fieldValue('all_branch',0) === 1 ? 'checked':'' }}>
            <label class="form-check-label" for="{{ $formId }}AllBranch">Semua cabang (pegawai mobile)</label>
        </div>
        <small class="text-muted">Pegawai dapat dikelola dan absen di semua cabang. Jika tidak dicentang, hanya berlaku di cabang asal.</small>
    </div>
    <div class="col-xl-6 mb-3">
        <label class="form-label" for="{{ $formId }}Photo">Foto pegawai</label>
        @if($formEmployee && $formEmployee->photo_path)<img src="{{ route('employees.photo',[$formEmployee->id,'photo']) }}" alt="Foto pegawai" class="d-block rounded mb-2" width="100" loading="lazy">@endif
        <input type="file" name="photo" id="{{ $formId }}Photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        <small class="text-muted">JPG, PNG, atau WebP, maksimal 5 MB. Kosongkan untuk mempertahankan foto saat ini.</small>
    </div>
    <div class="col-xl-6 mb-3">
        <label class="form-label" for="{{ $formId }}KtpPhoto">Foto KTP</label>
        @if($formEmployee && $formEmployee->ktp_photo_path)<a href="{{ route('employees.photo',[$formEmployee->id,'ktp']) }}" target="_blank" rel="noopener" class="d-block mb-2">Lihat foto KTP saat ini</a>@endif
        <input type="file" name="ktp_photo" id="{{ $formId }}KtpPhoto" class="form-control" accept="image/jpeg,image/png,image/webp">
        <small class="text-muted">JPG, PNG, atau WebP, maksimal 5 MB. Kosongkan untuk mempertahankan foto saat ini.</small>
    </div>
    <div class="col-xl-6 mb-3">
        <label class="form-label" for="{{ $formId }}Category">Kategori jabatan</label>
        <select name="position_category" id="{{ $formId }}Category" class="form-control">
            <option value="">Pilih kategori jabatan</option>
            @foreach(\App\Models\Employee::POSITION_CATEGORIES as $value=>$label)
                <option value="{{ $value }}" {{ $fieldValue('position_category') === $value ? 'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-xl-6 mb-3">
        <label class="form-label" for="{{ $formId }}StartDate">Tanggal mulai kerja</label>
        <input type="date" id="{{ $formId }}StartDate" name="employment_start_date" class="form-control" value="{{ $fieldValue('employment_start_date') }}">
    </div>
    <div class="col-xl-6 mb-3">
        <label class="form-label" for="{{ $formId }}ResignDate">Tanggal resign</label>
        <input type="date" id="{{ $formId }}ResignDate" name="resign_date" class="form-control" value="{{ $fieldValue('resign_date') }}">
    </div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Code">Kode pegawai</label><input id="{{ $formId }}Code" name="employee_code" class="form-control" required maxlength="50" value="{{ $fieldValue('employee_code') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Name">Nama lengkap</label><input id="{{ $formId }}Name" name="full_name" class="form-control" required maxlength="150" value="{{ $fieldValue('full_name') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Born">Tanggal lahir</label><input type="date" id="{{ $formId }}Born" name="born" class="form-control" max="{{ \Carbon\Carbon::today('Asia/Jakarta')->toDateString() }}" value="{{ $fieldValue('born') }}"><small class="text-muted">Opsional, untuk pengingat ulang tahun.</small></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Position">Detail jabatan</label><input id="{{ $formId }}Position" name="position" class="form-control" maxlength="100" value="{{ $fieldValue('position') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Phone">Telepon</label><input id="{{ $formId }}Phone" name="phone" class="form-control" maxlength="30" value="{{ $fieldValue('phone') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Active">Status</label><select id="{{ $formId }}Active" name="is_active" class="form-control"><option value="1" {{ (string)$fieldValue('is_active',1)==='1' ? 'selected':'' }}>Aktif</option><option value="0" {{ (string)$fieldValue('is_active',1)==='0' ? 'selected':'' }}>Nonaktif</option></select></div>
    <div class="col-12 mb-3">
        <label class="form-label" for="{{ $formId }}Note">Catatan / Note</label>
        <textarea name="note" id="{{ $formId }}Note" class="form-control" rows="3" maxlength="5000">{{ $fieldValue('note') }}</textarea>
    </div>
</div>
