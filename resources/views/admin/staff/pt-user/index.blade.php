<!-- Modal Add PT User -->
<div class="modal fade" id="modalAddPtUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-center">
        <div class="modal-content">
            <form action="{{ route('administrator.store') }}" method="POST">
                @csrf
                <input type="hidden" name="page" value="pt-user">
                <input type="hidden" name="role" value="PT">
                <input type="hidden" name="application_access[]" value="management">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Create PT User</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Branch</label>
                                <select name="branch_store_id" class="form-control" required>
                                    <option value="" disabled {{ old('branch_store_id') ? '' : 'selected' }}>
                                        &lt;- Choose Branch -&gt;
                                    </option>
                                    @foreach ($branch_stores as $branchStore)
                                        <option value="{{ $branchStore->id }}"
                                            {{ (string) old('branch_store_id') === (string) $branchStore->id ? 'selected' : '' }}>
                                            {{ $branchStore->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" value="{{ old('full_name') }}"
                                    class="form-control" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="form-control" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="PT (Read Only)" disabled>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-control" required>
                                    <option value="" disabled {{ old('gender') ? '' : 'selected' }}>
                                        &lt;- Choose -&gt;
                                    </option>
                                    <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="text" name="password" class="form-control" autocomplete="off" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit PT User -->
@foreach ($ptUsers as $item)
    <div class="modal fade" id="modalEditPtUser{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-center">
            <div class="modal-content">
                <form action="{{ route('administrator.update', $item->id) }}" method="POST">
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="page" value="pt-user">
                    <input type="hidden" name="application_access[]" value="management">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Edit PT User</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Branch</label>
                                    <select name="branch_store_id" class="form-control" required>
                                        @foreach ($branch_stores as $branchStore)
                                            <option value="{{ $branchStore->id }}"
                                                {{ (string) old('branch_store_id', $item->branch_store_id) === (string) $branchStore->id ? 'selected' : '' }}>
                                                {{ $branchStore->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name"
                                        value="{{ old('full_name', $item->full_name) }}"
                                        class="form-control" autocomplete="off" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" value="{{ old('email', $item->email) }}"
                                        class="form-control" autocomplete="off" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="Male" {{ old('gender', $item->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $item->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="text" name="password" class="form-control" autocomplete="off">
                                    <small>Leave blank if you don't want to change</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="tab-pane fade {{ $page == 'pt-user' ? 'show active' : '' }}" id="ptUser" role="tabpanel">
    <div class="card">
        <div class="card-body">
            <div class="col-xl-12">
                <h4>PT User List</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                @if ($pageType == 'new')
                    <div class="col-xl-12">
                        <div class="page-title flex-wrap">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalAddPtUser">
                                + New PT User
                            </button>
                        </div>
                    </div>
                @endif
                <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                    <div class="table-responsive full-data">
                        <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Gender</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ptUsers as $item)
                                    <tr>
                                        <td><h6>{{ optional($item->branchStore)->name ?: '-' }}</h6></td>
                                        <td><h6>{{ $item->full_name }}</h6></td>
                                        <td><h6>{{ $item->email }}</h6></td>
                                        <td><span class="badge badge-primary">PT</span></td>
                                        <td><h6>{{ $item->gender }}</h6></td>
                                        <td>
                                            @if ($pageType == 'new')
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditPtUser{{ $item->id }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('administrator.destroy', $item->id) }}"
                                                    method="POST"
                                                    data-delete-detail="PT User: {{ $item->full_name }} | {{ $item->email }}">
                                                    @method('delete')
                                                    @csrf
                                                    <input type="hidden" name="page" value="pt-user">
                                                    <button type="submit" class="btn light btn-danger btn-xs btn-block">Delete</button>
                                                </form>
                                            @else
                                                <a href="{{ route('restore-administrator', $item->id) }}"
                                                    onclick="return confirm('Restore PT User {{ addslashes($item->full_name) }}?')"
                                                    class="btn light btn-warning btn-xs btn-block">Restore</a>
                                                <form action="{{ route('administrator-force-delete', $item->id) }}"
                                                    method="POST"
                                                    data-delete-detail="PT User: {{ $item->full_name }} | {{ $item->email }}">
                                                    @method('delete')
                                                    @csrf
                                                    <button type="submit" class="btn light btn-danger btn-xs btn-block">Force Delete</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
