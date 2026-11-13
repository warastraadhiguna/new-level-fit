@php
    $restoreTemplateInput=$errors->any() && old('_template_form') && (string)old('_template_id')===(string)optional($template)->id;
@endphp
<div class="modal fade" id="{{ $formId }}" tabindex="-1" aria-labelledby="{{ $formId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ $template ? route('employee-shifts.update',$template->id) : route('employee-shifts.store') }}">
            @csrf @if($template) @method('PUT') @endif
            <input type="hidden" name="_template_id" value="{{ optional($template)->id }}">
            <input type="hidden" name="_template_form" value="1">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $formId }}Label">{{ $template ? 'Ubah':'Tambah' }} Template Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if($restoreTemplateInput)<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                @include('admin.employee-attendance.template-fields')
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </form>
    </div></div>
</div>
