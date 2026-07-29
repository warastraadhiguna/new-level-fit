<div class="row">
    <div class="col-xl-6">
        <div class="mb-3">
            <label class="form-label">Nama Cabang</label>
            <input type="text" name="name" value="{{ old('name', $branchStore->name ?? '') }}" class="form-control" required>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $branchStore->slug ?? '') }}" class="form-control">
            <small class="text-muted">Kosongkan jika ingin dibuat otomatis dari nama cabang.</small>
        </div>
    </div>
    <div class="col-xl-12">
        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="address" class="form-control" rows="3" required>{{ old('address', $branchStore->address ?? '') }}</textarea>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Kota</label>
            <input type="text" name="city" value="{{ old('city', $branchStore->city ?? '') }}" class="form-control" required>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $branchStore->phone ?? '') }}" class="form-control" required>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $branchStore->email ?? '') }}" class="form-control" required>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Payment Strict</label>
            <select name="is_payment_strict" class="form-control" required>
                @php
                    $paymentStrict = old('is_payment_strict', isset($branchStore) && $branchStore->is_payment_strict !== null ? (int) $branchStore->is_payment_strict : 1);
                @endphp
                <option value="1" {{ (string) $paymentStrict === '1' ? 'selected' : '' }}>Strict - unpaid cannot check in</option>
                <option value="0" {{ (string) $paymentStrict === '0' ? 'selected' : '' }}>Flexible - unpaid can check in</option>
            </select>
            <small class="text-muted">Berlaku untuk check-in membership dan PT di cabang ini.</small>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Branch Type</label>
            <select name="type" class="form-control" required>
                @php
                    $branchType = old('type', $branchStore->type ?? 'both');
                @endphp
                <option value="both" {{ $branchType === 'both' ? 'selected' : '' }}>Both - male & female</option>
                <option value="male" {{ $branchType === 'male' ? 'selected' : '' }}>Male only</option>
                <option value="female" {{ $branchType === 'female' ? 'selected' : '' }}>Female only</option>
            </select>
            <small class="text-muted">Dipakai untuk aturan akses cabang berdasarkan gender.</small>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Cicilan Membership Tahunan</label>
            @php $installmentEnabled = old('member_installment_enabled', isset($branchStore) ? (int) $branchStore->member_installment_enabled : 0); @endphp
            <select name="member_installment_enabled" class="form-control" required>
                <option value="0" {{ (string) $installmentEnabled === '0' ? 'selected' : '' }}>Nonaktif</option>
                <option value="1" {{ (string) $installmentEnabled === '1' ? 'selected' : '' }}>Aktif</option>
            </select>
            <small class="text-muted">Hanya paket dari cabang ini yang dapat memakai skema bulan 1 + 12.</small>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Masa Tenggang Cicilan (hari)</label>
            <input type="number" min="0" max="30" name="member_installment_grace_days"
                value="{{ old('member_installment_grace_days', $branchStore->member_installment_grace_days ?? 7) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Pembatalan Setelah (hari)</label>
            <input type="number" min="1" max="365" name="member_installment_cancel_days"
                value="{{ old('member_installment_cancel_days', $branchStore->member_installment_cancel_days ?? 30) }}" class="form-control" required>
            <small class="text-muted">Deposit bulan ke-12 hangus setelah kontrak dibatalkan.</small>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Diskon Membership</label>
            @php $memberDiscountEnabled = old('member_discount_enabled', isset($branchStore) ? (int) $branchStore->member_discount_enabled : 0); @endphp
            <select name="member_discount_enabled" class="form-control" required>
                <option value="0" {{ (string) $memberDiscountEnabled === '0' ? 'selected' : '' }}>Nonaktif</option>
                <option value="1" {{ (string) $memberDiscountEnabled === '1' ? 'selected' : '' }}>Aktif</option>
            </select>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="mb-3">
            <label class="form-label">Diskon PT</label>
            @php $trainerDiscountEnabled = old('trainer_discount_enabled', isset($branchStore) ? (int) $branchStore->trainer_discount_enabled : 0); @endphp
            <select name="trainer_discount_enabled" class="form-control" required>
                <option value="0" {{ (string) $trainerDiscountEnabled === '0' ? 'selected' : '' }}>Nonaktif</option>
                <option value="1" {{ (string) $trainerDiscountEnabled === '1' ? 'selected' : '' }}>Aktif</option>
            </select>
        </div>
    </div>
    <div class="col-xl-12">
        <div class="mb-3">
            <label class="form-label">Admin Logo</label>
            <input type="file" name="admin_logo" class="form-control" accept=".png,.jpg,.jpeg,.ico,.webp">
            <small class="text-muted">Logo ini dipakai untuk admin header, preloader, dan favicon.</small>
        </div>
        @if (!empty($branchStore) && !empty($branchStore->admin_logo_url))
            <div class="mb-2">
                <img src="{{ $branchStore->admin_logo_url }}" alt="{{ $branchStore->name }}" style="max-width: 220px; max-height: 70px; object-fit: contain;">
            </div>
        @elseif (!empty($branchStore))
            <small class="text-danger">Admin logo belum diisi.</small>
        @endif
    </div>
</div>
