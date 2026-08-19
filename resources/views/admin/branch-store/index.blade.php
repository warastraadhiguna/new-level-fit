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

        if (event.target.dataset.allRoles === '1' && event.target.checked) {
            roleCheckboxes.forEach(function (checkbox) {
                if (checkbox !== event.target) {
                    checkbox.checked = false;
                }
            });
            return;
        }

        if (event.target.checked) {
            const allRolesCheckbox = form.querySelector('.js-dashboard-finance-role[data-all-roles="1"]');
            if (allRolesCheckbox) {
                allRolesCheckbox.checked = false;
            }
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
                                <th>Kota</th>
                                <th>Kontak</th>
                                <th>Type</th>
                                <th>Payment Strict</th>
                                <th>Cicilan Membership</th>
                                <th>Diskon</th>
                                <th>POS & Inventory</th>
                                <th>Booking Class</th>
                                <th>Akses Keuangan Dashboard</th>
                                <th>Admin Logo</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($branchStores as $branchStore)
                                <tr>
                                    <td>
                                        <h6 class="mb-1">{{ $branchStore->name }}</h6>
                                        <small>{{ $branchStore->slug }}</small>
                                        <div class="mt-1">{{ $branchStore->address }}</div>
                                    </td>
                                    <td>{{ $branchStore->city }}</td>
                                    <td>
                                        <div>{{ $branchStore->phone }}</div>
                                        <div>{{ $branchStore->email }}</div>
                                    </td>
                                    <td>
                                        @if ($branchStore->type === 'male')
                                            <span class="badge badge-info">Male Only</span>
                                        @elseif ($branchStore->type === 'female')
                                            <span class="badge badge-danger">Female Only</span>
                                        @else
                                            <span class="badge badge-primary">Both</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($branchStore->is_payment_strict)
                                            <span class="badge badge-success">Strict</span>
                                        @else
                                            <span class="badge badge-warning">Flexible</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($branchStore->member_installment_enabled)
                                            <span class="badge badge-success">Aktif</span>
                                            <small class="d-block">{{ $branchStore->member_installment_grace_days }} hari grace / {{ $branchStore->member_installment_cancel_days }} hari batal</small>
                                        @else
                                            <span class="badge badge-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            Membership:
                                            <span class="badge {{ $branchStore->member_discount_enabled ? 'badge-success' : 'badge-secondary' }}">
                                                {{ $branchStore->member_discount_enabled ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            PT:
                                            <span class="badge {{ $branchStore->trainer_discount_enabled ? 'badge-success' : 'badge-secondary' }}">
                                                {{ $branchStore->trainer_discount_enabled ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $branchStore->pos_inventory_enabled ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $branchStore->pos_inventory_enabled ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">H-{{ (int) ($branchStore->class_booking_advance_days ?? 1) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $financeRoles = $branchStore->dashboard_finance_visible_roles ?: [\App\Models\BranchStore::DASHBOARD_FINANCE_ALL_ROLES];
                                        @endphp
                                        @if (in_array(\App\Models\BranchStore::DASHBOARD_FINANCE_ALL_ROLES, $financeRoles, true))
                                            <span class="badge badge-success">Semua Role</span>
                                        @else
                                            @foreach ($financeRoles as $financeRole)
                                                <span class="badge badge-info mb-1">
                                                    {{ \App\Models\BranchStore::DASHBOARD_FINANCE_ROLE_OPTIONS[$financeRole] ?? $financeRole }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if ($branchStore->admin_logo_url)
                                            <img src="{{ $branchStore->admin_logo_url }}" alt="{{ $branchStore->name }}" style="max-width: 180px; max-height: 60px; object-fit: contain;">
                                        @else
                                            <span class="text-danger">Belum diisi</span>
                                        @endif
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
