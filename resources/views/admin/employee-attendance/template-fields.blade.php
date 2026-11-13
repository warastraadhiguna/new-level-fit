@php
    $templateValue=function($field,$default='') use($template,$restoreTemplateInput) {
        $value=$template ? $template->$field : $default;
        if (in_array($field,['start_time','end_time'],true)) $value=substr($value,0,5);
        return $restoreTemplateInput ? old($field,$value) : $value;
    };
@endphp
<div class="row">
    <div class="col-12 mb-3">
        <label for="{{ $formId }}Name" class="form-label">Nama shift</label>
        <input id="{{ $formId }}Name" name="name" class="form-control" required maxlength="100" value="{{ $templateValue('name') }}" placeholder="Shift Pagi">
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $formId }}Start" class="form-label">Jam mulai</label>
        <input id="{{ $formId }}Start" type="time" name="start_time" class="form-control" required value="{{ $templateValue('start_time','06:00') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $formId }}End" class="form-label">Jam selesai</label>
        <input id="{{ $formId }}End" type="time" name="end_time" class="form-control" required value="{{ $templateValue('end_time','13:00') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $formId }}Grace" class="form-label">Toleransi (menit)</label>
        <input id="{{ $formId }}Grace" type="number" name="grace_minutes" class="form-control" required min="0" max="180" value="{{ $templateValue('grace_minutes',0) }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $formId }}Active" class="form-label">Status</label>
        <select id="{{ $formId }}Active" name="is_active" class="form-control">
            @foreach([1=>'Aktif',0=>'Nonaktif'] as $value=>$label)<option value="{{ $value }}" {{ (int)$templateValue('is_active',1)===$value ? 'selected':'' }}>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div class="col-12"><small class="text-muted">Jam selesai yang lebih kecil dari jam mulai dihitung sebagai hari berikutnya. Toleransi dikurangi dari perhitungan menit terlambat.</small></div>
</div>
