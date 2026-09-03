<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                        + New Member Package
                    </button>
                    <a href="{{ route('dataSoft') }}" class="btn btn-secondary">Old Member Package</a>
                </div>
            </div>
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Branch</th>                                
                                <th>Number Of Days</th>
                                <th>Package Price</th>
                                <th>Admin Price</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Payment Plan</th>
                                <th>Staff</th>
                                @if (Auth::user()->isAdmin())
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberPackage as $item)
                                <tr>
                                    <td>
                                        <h6>{{ $item->package_name }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->branchStore->name }}</h6>
                                    </td>                                    
                                    <td>
                                        <h6>{{ $item->days }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ formatRupiah($item->package_price) }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ formatRupiah($item->admin_price) }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->description }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->is_all_club == "1" ? "All Club" : "One Club"  }}</h6>
                                    </td>
                                    <td>
                                        @if ($item->is_installment_plan)
                                            <span class="badge badge-success">Cicilan 12 Bulan</span>
                                            <small class="d-block">{{ formatRupiah($item->installment_monthly_amount) }}/bulan</small>
                                        @else
                                            <span class="badge badge-secondary">Biasa</span>
                                        @endif
                                    </td>
                                    <td>
                                        <h6>{{ $item->users->full_name }}</h6>
                                    </td>
                                    @if (Auth::user()->isAdmin())
                                        <td>
                                            <div>
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit{{ $item->id }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('member-package.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Member Package Data ? ')"
                                                    method="POST">
                                                    @method('delete')
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn light btn-danger btn-xs btn-block">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!--/column-->
        </div>
    </div>
</div>
@include('admin.member-package.create')
@include('admin.member-package.edit')
@include('admin.partials.package-submit-guard')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.member-package-form').forEach(function (form) {
            var branchSelect = form.querySelector('[name="branch_store_id"]');
            var installmentSelect = form.querySelector('[name="is_installment_plan"]');
            var monthlyAmount = form.querySelector('[name="installment_monthly_amount"]');
            var installmentSettings = form.querySelectorAll('.installment-setting');

            if (!branchSelect || !installmentSelect || !monthlyAmount) {
                return;
            }

            function updateInstallmentVisibility() {
                var selectedBranch = branchSelect.options[branchSelect.selectedIndex];
                var isEnabled = selectedBranch
                    && selectedBranch.getAttribute('data-installment-enabled') === '1';

                installmentSettings.forEach(function (element) {
                    element.style.display = isEnabled ? '' : 'none';
                });

                monthlyAmount.disabled = !isEnabled;

                if (!isEnabled) {
                    installmentSelect.value = '0';
                    monthlyAmount.value = '';
                }
            }

            branchSelect.addEventListener('change', updateInstallmentVisibility);
            updateInstallmentVisibility();
        });

        @if ($errors->any())
            var failedForm = @json(old('form_context', 'create'));
            var failedModalId = failedForm.indexOf('edit-') === 0
                ? '#modalEdit' + failedForm.replace('edit-', '')
                : '#modalAdd';
            var failedModalElement = document.querySelector(failedModalId);

            if (failedModalElement && window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(failedModalElement).show();
            } else if (failedModalElement && window.jQuery) {
                window.jQuery(failedModalElement).modal('show');
            }
        @endif
    });
</script>
