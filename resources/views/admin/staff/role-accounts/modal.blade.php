<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-center"><div class="modal-content">
        <form method="POST" action="{{ $account ? route($rolePrefix.'-accounts.update',$account->id) : route($rolePrefix.'-accounts.store') }}">
            @csrf @if($account) @method('PUT') @endif
            <input type="hidden" name="_role_form" value="{{ $formKey }}">
            <div class="modal-header"><h1 class="modal-title fs-5" id="{{ $modalId }}-title">{{ $account ? 'Edit' : 'Create' }} {{ $roleLabel }}</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                @if($errors->any() && old('_role_form')===$formKey)<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @include('admin.role-accounts.fields')
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button></div>
        </form>
    </div></div>
</div>
@php
    $reopen=($errors->any() && old('_role_form')===$formKey)
        || (!$errors->any() && $account && $page===$rolePrefix && (string)request('edit_account')===(string)$account->id);
@endphp
@if($reopen)
<script>document.addEventListener('DOMContentLoaded',function(){const modal=document.getElementById(@json($modalId));if(modal && window.bootstrap)bootstrap.Modal.getOrCreateInstance(modal).show();});</script>
@endif
