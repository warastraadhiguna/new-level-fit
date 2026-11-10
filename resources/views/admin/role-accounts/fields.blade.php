@php
    $useOld=old('_role_form')===$formKey;
    $value=function($field,$default='') use($useOld) { return $useOld ? old($field,$default) : $default; };
@endphp
<div class="row">
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-branch">Branch</label><select id="{{ $formKey }}-branch" class="form-control" disabled><option>{{ optional(Auth::user()->branchStore)->name }}</option></select><small class="text-muted">Cabang mengikuti cabang login.</small></div>
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-name">Full Name</label><input id="{{ $formKey }}-name" name="full_name" class="form-control" required maxlength="200" autocomplete="off" value="{{ $value('full_name',$account ? $account->full_name : '') }}"></div>
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-email">Email</label><input type="email" id="{{ $formKey }}-email" name="email" class="form-control" required maxlength="255" autocomplete="off" value="{{ $value('email',$account ? $account->email : '') }}"></div>
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-role">Role</label><input id="{{ $formKey }}-role" class="form-control" value="{{ $roleLabel }}" readonly></div>
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-gender">Gender</label><select id="{{ $formKey }}-gender" name="gender" class="form-control" required><option value="">&lt;- Choose -&gt;</option><option value="Male" {{ $value('gender',$account ? $account->gender : '')==='Male' ? 'selected':'' }}>Male</option><option value="Female" {{ $value('gender',$account ? $account->gender : '')==='Female' ? 'selected':'' }}>Female</option></select></div>
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-password">Password {{ $account ? '(leave blank to keep)' : '' }}</label><input type="password" id="{{ $formKey }}-password" name="password" class="form-control" minlength="8" autocomplete="new-password" @if(!$account) required @endif></div>
    <div class="col-xl-6 mb-3"><label class="form-label" for="{{ $formKey }}-confirmation">Confirm Password</label><input type="password" id="{{ $formKey }}-confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" @if(!$account) required @endif></div>
</div>
