<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('trainer-session.move-branch', $trainerSession->id) }}" method="POST"
                onsubmit="return confirm('Move this PT registration to the selected package branch?')">
                @method('PUT')
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="alert alert-warning">
                    Branch will follow the selected PT package. Current trainer, price, duration, and sessions will be kept.
                </div>

                <div class="row">
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <input type="text" class="form-control"
                                value="{{ data_get($trainerSession, 'members.full_name', '-') }} | {{ data_get($trainerSession, 'members.member_code', '-') }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Current Branch</label>
                            <input type="text" class="form-control"
                                value="{{ data_get($trainerSession, 'branchStore.name', data_get($trainerSession, 'branch_store_id')) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Current Trainer</label>
                            <input type="text" class="form-control"
                                value="{{ data_get($trainerSession, 'personalTrainers.full_name', '-') }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Current PT Package</label>
                            <input type="text" class="form-control"
                                value="{{ data_get($trainerSession, 'trainerPackages.package_name', '-') }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Current PT Billing</label>
                            <input type="text" class="form-control"
                                value="{{ formatRupiah($trainerSession->package_price) }} | Admin {{ formatRupiah($trainerSession->admin_price) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label class="form-label">Target PT Package</label>
                            @if ($targetTrainerPackages->isEmpty())
                                <div class="alert alert-info mb-0">
                                    {{ $moveBranchUnavailableReason ?? 'No PT package is available from another branch for this member.' }}
                                </div>
                            @else
                                <select id="single-select" name="trainer_package_id" class="form-control"
                                    data-current-package-price="{{ (int) $trainerSession->package_price }}"
                                    data-current-admin-price="{{ (int) $trainerSession->admin_price }}"
                                    required>
                                    <option value=""><- Choose target PT package -></option>
                                    @foreach ($targetTrainerPackages as $item)
                                        <option value="{{ $item->id }}"
                                            data-package-price="{{ (int) $item->package_price }}"
                                            data-admin-price="{{ (int) $item->admin_price }}"
                                            {{ old('trainer_package_id') == $item->id ? 'selected' : '' }}>
                                            {{ data_get($item, 'branchStore.name', 'No branch') }} |
                                            {{ $item->package_name }} |
                                            {{ $item->number_of_session }} Sessions |
                                            {{ $item->days }} Days |
                                            {{ formatRupiah($item->package_price) }} |
                                            Admin {{ formatRupiah($item->admin_price) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="moveBranchPriceWarning" class="alert alert-danger mt-3 d-none mb-0">
                                    Target package price is different from the current PT billing.
                                    Payment amounts will not be updated automatically.
                                    <div class="mt-2">
                                        Current billing: <strong id="moveBranchCurrentPrice">{{ formatRupiah($trainerSession->package_price) }} | Admin {{ formatRupiah($trainerSession->admin_price) }}</strong>
                                    </div>
                                    <div>
                                        Target package: <strong id="moveBranchTargetPrice">-</strong>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary" {{ $targetTrainerPackages->isEmpty() ? 'disabled' : '' }}>
                        Move Branch
                    </button>
                    <a href="{{ route('trainer-session.index') }}" class="btn btn-danger">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        var packageSelect = document.getElementById('single-select');
        var warningBox = document.getElementById('moveBranchPriceWarning');
        var targetPriceText = document.getElementById('moveBranchTargetPrice');

        if (!packageSelect || !warningBox || !targetPriceText) {
            return;
        }

        var formatter = new Intl.NumberFormat('id-ID');

        function formatRupiahJs(value) {
            return 'Rp. ' + formatter.format(value || 0);
        }

        function updateMoveBranchWarning() {
            var selectedOption = packageSelect.options[packageSelect.selectedIndex];
            var currentPackagePrice = Number(packageSelect.dataset.currentPackagePrice || 0);
            var currentAdminPrice = Number(packageSelect.dataset.currentAdminPrice || 0);

            if (!selectedOption || !selectedOption.value) {
                warningBox.classList.add('d-none');
                targetPriceText.textContent = '-';
                return;
            }

            var targetPackagePrice = Number(selectedOption.dataset.packagePrice || 0);
            var targetAdminPrice = Number(selectedOption.dataset.adminPrice || 0);
            var hasDifferentPrice = currentPackagePrice !== targetPackagePrice || currentAdminPrice !== targetAdminPrice;

            targetPriceText.textContent = formatRupiahJs(targetPackagePrice) + ' | Admin ' + formatRupiahJs(targetAdminPrice);
            warningBox.classList.toggle('d-none', !hasDifferentPrice);
        }

        packageSelect.addEventListener('change', updateMoveBranchWarning);
        updateMoveBranchWarning();
    })();
</script>
