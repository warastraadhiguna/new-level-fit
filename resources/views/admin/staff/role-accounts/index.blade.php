<div class="tab-pane fade {{ $page===$rolePrefix ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel">
    <div class="card"><div class="card-body"><h4>{{ $roleLabel }} List</h4></div></div>
    @if($pageType==='new')
        <div class="page-title flex-wrap"><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddRole-{{ $rolePrefix }}">+ New {{ $roleLabel }}</button></div>
        @include('admin.staff.role-accounts.modal',['account'=>null,'formKey'=>$rolePrefix.'-create','modalId'=>'modalAddRole-'.$rolePrefix])
    @endif
    <div class="table-responsive full-data">
        <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="staff-{{ $rolePrefix }}-table" style="width:100%">
            <thead><tr><th>Branch</th><th>Full Name</th><th>Email</th><th>Role</th><th>Gender</th><th>Application Access</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($accounts as $item)
                    <tr><td><h6>{{ optional($item->branchStore)->name }}</h6></td><td><h6>{{ $item->full_name }}</h6></td><td><h6>{{ $item->email }}</h6></td><td><span class="badge badge-primary">{{ $accountRole }}</span></td><td>{{ $item->gender }}</td>
                        <td><span class="badge {{ $item->hasApplicationAccess('management') ? 'badge-primary' : 'badge-secondary' }}">{{ $item->hasApplicationAccess('management') ? 'Management' : 'No Access' }}</span></td>
                        <td>
                            @if($pageType==='new')
                                <button type="button" class="btn light btn-warning btn-xs mb-1 btn-block" data-bs-toggle="modal" data-bs-target="#modalEditRole-{{ $rolePrefix }}-{{ $item->id }}">Edit</button>
                                <form method="POST" action="{{ route($rolePrefix.'-accounts.destroy',$item->id) }}" onsubmit="return confirm('Delete Data?')">@csrf @method('DELETE')<button class="btn light btn-danger btn-xs btn-block">Delete</button></form>
                            @else
                                <form method="POST" action="{{ route($rolePrefix.'-accounts.restore',$item->id) }}" onsubmit="return confirm('Restore Data?')">@csrf<button class="btn light btn-warning btn-xs btn-block">Restore</button></form>
                                <form method="POST" action="{{ route($rolePrefix.'-accounts.force-delete',$item->id) }}" onsubmit="return confirm('Permanently delete?')">@csrf @method('DELETE')<button class="btn light btn-danger btn-xs btn-block">Force Delete</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($pageType==='new')
        @foreach($accounts as $item)
            @include('admin.staff.role-accounts.modal',['account'=>$item,'formKey'=>$rolePrefix.'-edit-'.$item->id,'modalId'=>'modalEditRole-'.$rolePrefix.'-'.$item->id])
        @endforeach
    @endif
</div>
