<!-- Modal Add -->
<div class="modal fade" id="modalAddBranchStore" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('secret-branch-store.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Tambah Cabang</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.branch-store.partials.form-fields', ['branchStore' => null])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('change', function (event) {
        if (!event.target.classList.contains('js-dashboard-finance-role')) {
            return;
        }

        const form = event.target.closest('form');
        const roleCheckboxes = form.querySelectorAll('.js-dashboard-finance-role');

        if (event.target.dataset.exclusiveRole === '1' && event.target.checked) {
            roleCheckboxes.forEach(function (checkbox) {
                if (checkbox !== event.target) {
                    checkbox.checked = false;
                }
            });
            return;
        }

        if (event.target.checked) {
            form.querySelectorAll('.js-dashboard-finance-role[data-exclusive-role="1"]').forEach(function (checkbox) {
                checkbox.checked = false;
            });
        }
    });
</script>

@foreach ($branchStores as $branchStore)
    <div class="modal fade" id="modalEditBranchStore{{ $branchStore->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('secret-branch-store.update', $branchStore->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Edit Cabang</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.branch-store.partials.form-fields', ['branchStore' => $branchStore])
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

<div class="row">
    <div class="col-xl-12">
        <div class="row">
            @if ($errors->any())
                <div class="col-xl-12">
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            <div class="col-xl-12">
                <div class="page-title flex-wrap">
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddBranchStore">
                            + New Branch Store
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="myTable">
                        <thead>
                            <tr>
                                <th>Cabang</th>
                                <th>Lokasi</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($branchStores as $branchStore)
                                <tr>
                                    <td>
                                        <h6 class="mb-0">{{ $branchStore->name }}</h6>
                                    </td>
                                    <td>
                                        <div>{{ $branchStore->address }}</div>
                                        <small class="text-muted">{{ $branchStore->city }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-warning btn-xs" data-bs-toggle="modal" data-bs-target="#modalEditBranchStore{{ $branchStore->id }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('secret-branch-store.destroy', $branchStore->id) }}" method="POST" onsubmit="return confirm('Hapus cabang ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn light btn-danger btn-xs">Delete</button>
                                            </form>
                                        </div>
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
