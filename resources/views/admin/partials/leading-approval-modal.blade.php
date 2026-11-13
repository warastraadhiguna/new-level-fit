@php
    $approvalModalId='leadingApproval-'.$approvalKind.'-'.$approvalRecord->id;
    $approvalHasErrors=$errors->any() && old('_leading_approval')===$approvalModalId;
@endphp
<div class="modal fade" id="{{ $approvalModalId }}" tabindex="-1" aria-labelledby="{{ $approvalModalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form action="{{ route($approvalRoute,$approvalRecord->id) }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="_leading_approval" value="{{ $approvalModalId }}">
            <input type="hidden" name="is_approved" value="1">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $approvalModalId }}Label">{{ $approvalTitle }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($approvalHasErrors)<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                <strong>{{ $approvalRecord->member_name }}</strong>
                <p class="text-muted">{{ $approvalRecord->member_code }} · {{ $approvalRecord->start_date }}</p>
                <label class="form-label" for="{{ $approvalModalId }}Description">Description</label>
                <textarea name="description" id="{{ $approvalModalId }}Description" class="form-control" rows="4" maxlength="5000" required>{{ $approvalHasErrors ? old('description') : $approvalRecord->description }}</textarea>
                <small class="text-muted">Description wajib diubah sebelum approval disimpan.</small>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Approve</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div></div>
</div>
@if($approvalHasErrors)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(@json($approvalModalId))).show();
    });
</script>
@endif
