# Pengaturan akses fitur per role

Owner membuka **Pengaturan Akses Role** (`/role-feature-access`) pada aplikasi
utama. Matriks checkbox berlaku untuk cabang login Owner saja. Role yang diatur:
Administrator, CS, CS POS, FC, PT User, HRD dan Finance. Owner selalu mempunyai
akses dan tidak dapat dikunci melalui matriks ini.

Perubahan berlaku pada request berikutnya tanpa login ulang. Menu tujuan yang
dinonaktifkan disembunyikan; middleware juga menolak request langsung, termasuk
POST/PUT/DELETE dan URL alternatif. Menyembunyikan menu saja bukan otorisasi.
Penyimpanan satu matriks lengkap dilakukan dalam transaksi. Cabang dan pencatat
perubahan diambil dari akun login, bukan parameter form.

Aturan khusus tetap berlaku: PT hanya lihat, ekspor Excel/perangkat absensi/
akses aplikasi Management tetap diperiksa. Export Tip-Tap secara default hanya
Owner, dan dapat diberikan ke role lain melalui checkbox tersendiri. Tanda —
pada matriks berarti fitur berada di luar cakupan role yang didukung. Pengaturan
ini tidak mengubah role menjadi Admin atau memberi hak akun lain secara otomatis.
HRD dapat diberi Revenue; Finance dapat diberi Data Pegawai/Pendaftaran Wajah.
Controller pegawai dan Revenue tetap membatasi data pada cabang login.

Secret Branch Store tetap menjadi sakelar fitur cabang (POS, PT Free, wajah,
Revenue dll.). Checkbox izin role tidak mengaktifkan fitur cabang yang mati.
Sebagai contoh HRD dengan Revenue dicentang tetap harus diizinkan oleh pengaturan
visibilitas Revenue di cabang tersebut. Izin fitur memberi akses ke operasi yang
memang didukung fitur/role, bukan izin edit tambahan untuk role baca.

Tanpa baris pengaturan, matriks menggunakan default role dalam
`App\Support\RoleFeatureAccess`. Tabel kosong tidak membuat akun atau grant baru.
Tanpa tabel, aplikasi menggunakan default dan halaman Owner memberi petunjuk
SQL; penyimpanan dinonaktifkan. Untuk menambah fitur baru, daftarkan fitur,
cakupan role, pemetaan route (termasuk endpoint mutasi/alias), dan menu, lalu uji
akses URL langsung. Route management yang belum dipetakan masuk kategori
Fitur Operasional Lain; HRD/Finance/PT tidak mendapat akses fallback tersebut.

## Database dan deploy

Tabel `role_feature_permissions` berada di **database utama aplikasi**, sama
seperti tabel `users`, bukan koneksi terpisah `face_enrollment`. Tidak ada perubahan
Python atau aplikasi kiosk. Gunakan migration
`2026_11_10_000001_create_role_feature_permissions.php` **atau** SQL manual
`database/sql/2026_11_10_role_feature_permissions.sql`. Tidak perlu menjalankan
keduanya. Migration aman jika tabel sudah dibuat manual.

Deploy aplikasi utama dan bersihkan cache config/route/view sesuai versi PHP
aplikasi. Pengaturan default tidak membutuhkan INSERT manual; simpan melalui
halaman Owner agar seluruh matriks dan `updated_by` konsisten.

Secret Branch Store tidak masuk matriks izin role. Akses halaman tersebut tetap
mengikuti pembatasan khusus yang sudah ada dan tidak dapat diberikan melalui matriks.

Export Tip-Tap merupakan pengecualian yang dapat didelegasikan oleh Owner.
Semua role non-Owner default ditolak, termasuk jika belum ada baris pengaturan.
Grant berlaku bagi role pada cabang login; sakelar Tip-Tap cabang tetap berlaku.
Paket transfer mencakup data seluruh cabang (termasuk tabel akun), bukan export
terbatas cabang. PT yang diberi izin boleh membuat file lewat endpoint POST
export tanpa memperoleh izin mengubah data/transaksi. Tidak ada migration atau
SQL tambahan; fitur memakai `feature_key=tip_tap_transfer` pada tabel izin yang ada.
