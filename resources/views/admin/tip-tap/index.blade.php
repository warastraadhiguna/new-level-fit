<div class="card">
    <div class="card-body">
        <h4>Tip-Tap versi 1 — Tempat Sampah</h4>
        <p>Data di sini tidak muncul dalam fitur operasional dan tidak dihitung dalam omzet. Restore mengembalikan history dan pembayaran seperti sebelum dihapus.</p>
        <p>Restore member terlebih dahulu sebelum memulihkan registrasinya. Registrasi yang dihapus terpisah tetap perlu direstore terpisah. Hapus permanen member juga menghapus seluruh registrasinya yang ada di tempat sampah.</p>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if ($entries->total() > 0)
            <details class="mb-4">
                <summary class="text-danger">Hapus permanen semua ({{ $entries->total() }} arsip)…</summary>
                <form action="{{ route('tip-tap.purge-all') }}" method="POST" class="mt-2"
                    onsubmit="return confirm('Hapus SELURUH isi tempat sampah di semua halaman dan cabang? Data tidak dapat direstore. Lanjutkan?')">
                    @csrf
                    @method('DELETE')
                    <p>Seluruh isi tempat sampah di semua halaman dan cabang, termasuk history dan foto yang diarsipkan, akan dihapus selamanya. Data aktif tetap tersimpan.</p>
                    <label for="confirm-purge-all">Ketik HAPUS SEMUA PERMANEN untuk melanjutkan.</label>
                    <input id="confirm-purge-all" name="confirmation" class="form-control mb-2"
                        required autocomplete="off" pattern="HAPUS SEMUA PERMANEN">
                    <button type="submit" class="btn btn-danger">Hapus permanen semua</button>
                </form>
            </details>
        @endif
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Data</th><th>Jenis</th><th>Dihapus pada</th><th>Tindakan</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->label }}</td>
                            <td>{{ ['member' => 'Member + seluruh history', 'membership' => 'Membership', 'pt' => 'PT registration'][$entry->kind] }}</td>
                            <td>{{ $entry->deleted_at }}</td>
                            <td>
                                <form action="{{ route('tip-tap.restore', $entry->id) }}" method="POST" class="mb-2"
                                    onsubmit="return confirm('Restore data beserta history dan omzetnya?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Restore</button>
                                </form>
                                <details>
                                    <summary class="text-danger">Hapus permanen…</summary>
                                    <form action="{{ route('tip-tap.purge', $entry->id) }}" method="POST" class="mt-2"
                                        onsubmit="return confirm('Data ini akan dihapus SELAMANYA dan tidak dapat direstore. Lanjutkan?')">
                                        @csrf
                                        @method('DELETE')
                                        <label for="confirm-{{ $entry->id }}">Ketik HAPUS PERMANEN untuk menghapus selamanya.</label>
                                        <input id="confirm-{{ $entry->id }}" name="confirmation" class="form-control mb-2" required autocomplete="off" pattern="HAPUS PERMANEN">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus permanen</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">Tempat sampah kosong.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links() }}
    </div>
</div>
