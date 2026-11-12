@php
    $fieldValue = function ($field, $default = '') use ($formEmployee, $restoreInput) {
        $value = $formEmployee ? $formEmployee->$field : $default;
        if ($field === 'born' && $value) $value = $value->format('Y-m-d');
        if ($field === 'is_active') $value = (int) $value;
        return $restoreInput ? old($field, $value) : $value;
    };
@endphp
<div class="row">
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Code">Kode pegawai</label><input id="{{ $formId }}Code" name="employee_code" class="form-control" required maxlength="50" value="{{ $fieldValue('employee_code') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Name">Nama lengkap</label><input id="{{ $formId }}Name" name="full_name" class="form-control" required maxlength="150" value="{{ $fieldValue('full_name') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Born">Tanggal lahir</label><input type="date" id="{{ $formId }}Born" name="born" class="form-control" max="{{ \Carbon\Carbon::today('Asia/Jakarta')->toDateString() }}" value="{{ $fieldValue('born') }}"><small class="text-muted">Opsional, untuk pengingat ulang tahun.</small></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Position">Jabatan</label><input id="{{ $formId }}Position" name="position" class="form-control" maxlength="100" value="{{ $fieldValue('position') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Phone">Telepon</label><input id="{{ $formId }}Phone" name="phone" class="form-control" maxlength="30" value="{{ $fieldValue('phone') }}"></div>
<div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formId }}Active">Status</label><select id="{{ $formId }}Active" name="is_active" class="form-control"><option value="1" {{ (string)$fieldValue('is_active',1)==='1' ? 'selected':'' }}>Aktif</option><option value="0" {{ (string)$fieldValue('is_active',1)==='0' ? 'selected':'' }}>Nonaktif</option></select></div>
</div>
