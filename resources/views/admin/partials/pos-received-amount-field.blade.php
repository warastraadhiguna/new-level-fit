@if (optional(Auth::user()->branchStore)->pos_inventory_enabled)
    <div class="{{ $columnClass ?? 'col-xl-6' }}" id="{{ $fieldId ?? 'received_amount' }}">
        <div class="mb-3">
            <label class="form-label">Uang Bayar</label>
            <input type="text" name="received_amount" value="{{ old('received_amount') }}"
                class="form-control pos-received-amount" placeholder="0" required autocomplete="off"
                inputmode="numeric">
            <small class="text-muted">Nominal uang yang diterima dari pelanggan untuk menghitung kembalian.</small>
        </div>
    </div>

    <script>
        (function () {
            document.querySelectorAll('input.pos-received-amount').forEach(function (input) {
                if (input.dataset.rupiahFormatterInitialized === 'true') {
                    return;
                }

                input.dataset.rupiahFormatterInitialized = 'true';

                function formatRupiah() {
                    var digits = input.value.replace(/\D/g, '');
                    input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }

                input.addEventListener('input', formatRupiah);
                formatRupiah();
            });
        })();
    </script>
@endif
