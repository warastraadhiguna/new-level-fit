@if (optional(Auth::user()->branchStore)->pos_inventory_enabled)
    <div class="{{ $columnClass ?? 'col-xl-6' }}" id="{{ $fieldId ?? 'received_amount' }}">
        <div class="mb-3">
            <label class="form-label">Uang Bayar</label>
            <input type="text" name="received_amount" value="{{ old('received_amount') }}"
                class="form-control rupiah" placeholder="0" required autocomplete="off">
            <small class="text-muted">Nominal uang yang diterima dari pelanggan untuk menghitung kembalian.</small>
        </div>
    </div>
@endif
