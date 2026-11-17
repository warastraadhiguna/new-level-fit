<div class="card">
    <div class="card-body">
        <h4>Export Data untuk Tip-Tap</h4>
        <p class="mb-2">
            File ini berisi data member, membership, PT/PT Free, pembayaran, freeze, serta check-in/out yang
            dibuat atau berubah dalam rentang waktu yang dipilih.
        </p>
        <div class="alert alert-info">
            Data diambil setelah tanggal awal hingga tanggal akhir.
            Setelah import berhasil, gunakan waktu akhir yang ditampilkan Tip-Tap sebagai waktu awal export berikutnya.
        </div>

        <form action="{{ route('tip-tap-transfer.export') }}" method="POST" class="row align-items-end" data-allow-repeat-submit>
            @csrf
            <div class="col-md-4 mb-3">
                <label for="from_at" class="form-label">Tanggal awal (setelah)</label>
                <input id="from_at" name="from_at" type="datetime-local" step="1" class="form-control @error('from_at') is-invalid @enderror"
                    value="{{ old('from_at', $defaultFrom) }}" required>
                @error('from_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="until_at" class="form-label">Tanggal akhir (sampai dengan)</label>
                <input id="until_at" name="until_at" type="datetime-local" step="1" class="form-control @error('until_at') is-invalid @enderror"
                    value="{{ old('until_at', $defaultUntil) }}" max="{{ now()->format('Y-m-d\TH:i:s') }}" required>
                @error('until_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <button type="submit" class="btn btn-primary">Download File Transfer</button>
            </div>
        </form>

        <p class="text-muted mb-0">
            Gunakan file hasil export untuk memperbarui data di Tip-Tap.
        </p>
    </div>
</div>
