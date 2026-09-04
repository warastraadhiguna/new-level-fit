<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('members.store-membership', $member->id) }}" method="POST" id="createMembershipForm">
                @csrf
                <h3 class="mb-4">Create Membership</h3>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" value="{{ $member->full_name }}" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" value="{{ $member->phone_number }}" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Member Number</label>
                                <input type="text" value="{{ $member->member_code ?? 'No Member Code' }}" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" value="{{ $member->card_number ?? 'No Card Number' }}" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Member Package</label>
                                <select name="member_package_id" class="form-control" id="single-select" required>
                                    <option value="">
                                        <- Choose ->
                                    </option>
                                    @foreach ($memberPackage as $item)
                                        <option value="{{ $item->id }}"
                                            data-package-price="{{ $item->package_price }}"
                                            data-admin-price="{{ $item->admin_price }}"
                                            {{ old('member_package_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->package_name }} |
                                            {{ $item->days }} Days |
                                            {{ formatRupiah($item->package_price) }} |
                                            {{ formatRupiah($item->admin_price) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="text" name="start_date" value="{{ old('start_date') }}"
                                    class="form-control editDate mdate-custom3" required autocomplete="off"
                                    placeholder="Choose start date">
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Method Payment</label>
                                <select name="method_payment_id" class="form-control" id="single-select5" required>
                                    <option value="">
                                        <- Choose ->
                                    </option>
                                    @foreach ($methodPayment as $item)
                                        <option value="{{ $item->id }}" {{ old('method_payment_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if (optional(Auth::user()->branchStore)->member_discount_enabled)
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Diskon Membership</label>
                                    <input type="text" name="discount_amount" value="{{ old('discount_amount', 0) }}"
                                        class="form-control rupiah" placeholder="0">
                                    <small class="text-muted">Diskon nominal rupiah, biaya admin tetap dihitung.</small>
                                </div>
                            </div>
                        @endif
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">First Payment</label>
                                <input type="text" name="first_payment" value="{{ old('first_payment') }}"
                                    class="form-control" placeholder="First Payment" required>
                            </div>
                        </div>
                        @include('admin.partials.pos-received-amount-field')
                        @include('admin.partials.payment-deadline-field')
                        @if (Auth::user()->role != 'FC')
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Fitness Consultant</label>
                                    <select id="single-select3" name="fc_id" class="form-control">
                                        <option value="">
                                            <- Choose ->
                                        </option>
                                        @foreach ($fitnessConsultant as $item)
                                            <option value="{{ $item->id }}" {{ old('fc_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label text-primary">Description</label>
                                <textarea class="form-control" name="description" rows="6"
                                    placeholder="Enter Description">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Save Membership</button>
                    <a href="{{ route('members.index') }}" class="btn btn-info text-right">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        const createMembershipForm = document.getElementById('createMembershipForm');

        if (!createMembershipForm) {
            return;
        }

        const firstPaymentInput = createMembershipForm.querySelector('input[name="first_payment"]');

        function formatThousands(value) {
            const numbersOnly = (value || '').replace(/\D/g, '');
            return numbersOnly.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        if (firstPaymentInput) {
            firstPaymentInput.value = formatThousands(firstPaymentInput.value);

            firstPaymentInput.addEventListener('input', function(e) {
                e.target.value = formatThousands(e.target.value);
            });
        }

        createMembershipForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });

        createMembershipForm.addEventListener('submit', function(e) {
            const memberPackage = createMembershipForm.querySelector('select[name="member_package_id"]');
            const selectedPackage = memberPackage ? memberPackage.options[memberPackage.selectedIndex] : null;
            const firstPayment = parseInt(((firstPaymentInput ? firstPaymentInput.value : '') || '').replace(/\D/g, ''), 10) || 0;
            const packagePrice = parseInt(selectedPackage ? selectedPackage.getAttribute('data-package-price') || 0 : 0, 10);
            const adminPrice = parseInt(selectedPackage ? selectedPackage.getAttribute('data-admin-price') || 0 : 0, 10);
            const totalPrice = packagePrice + adminPrice;

            if (totalPrice > 0 && firstPayment > totalPrice) {
                const confirmed = confirm('The first payment is higher than the package price plus admin fee. Are you sure you want to continue?');

                if (!confirmed) {
                    e.preventDefault();
                }
            }
        });
    })();
</script>
