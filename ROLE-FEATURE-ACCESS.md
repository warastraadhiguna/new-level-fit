# Izin CRUD role per cabang

Pengaturan `/role-feature-access` sekarang memiliki Create (tambah), Read (lihat),
Update (ubah/restore), dan Delete (hapus) per fitur dan role. Read wajib aktif
sebelum aksi lain. Owner tetap tidak dapat dikunci. Aksi yang tidak didukung
fitur atau role tidak ditawarkan: PT hanya Read, Approval hanya Read/Update,
Revenue/Dashboard/laporan/Export Tip-Tap hanya Read, enrollment/check-in/pembayaran
hanya Create/Read/Delete. Create pada Check-in mencatat masuk maupun keluar.

Selain modul lama, ada pengaturan terpisah untuk Approval Membership, Approval PT,
Check-in Membership, Check-in PT, Pembayaran Membership, dan Pembayaran PT.
Pembayaran di sini mengatur penambahan/penghapusan pembayaran lewat endpoint
pembayaran; pembayaran awal yang dibuat bersama registrasi tetap merupakan
bagian Create registrasi. Diskon/cicilan pada form registrasi tetap mengikuti
sakelar cabang dan validasi transaksi yang sudah ada.

Sakelar Secret Branch Store digunakan bersama untuk menyaring matriks dan
memeriksa endpoint: PT Free, POS, pegawai/wajah, Export Tip-Tap, serta kedua
Approval. Kolom CS POS hilang ketika POS cabang nonaktif. Pilihan role Revenue
mengikuti visibilitas Revenue cabang. Pengaturan role tidak dapat menyalakan
fitur cabang yang mati. Fitur tanpa sakelar cabang tetap mengikuti izin role.

Pemeriksaan dilakukan di server, termasuk API gate dan alias GET lama untuk
check-in, restore, reset check-in, serta form create/edit. Tidak bergantung pada
penyembunyian menu. Kontrol link/form/modal yang tidak diizinkan juga disembunyikan
pada halaman operasional; POST/PUT/DELETE langsung tetap ditolak. Pembatasan role,
akses Management, cakupan cabang, aturan bisnis, dan batas ekspor Excel tetap berlaku.

## SQL tambahan untuk CRUD

Jalankan satu kali pada **database utama Management**, tempat tabel
`role_feature_permissions` dan `users` berada, bukan database absensi:

```sql
ALTER TABLE `role_feature_permissions`
    ADD COLUMN `can_create` TINYINT(1) NULL DEFAULT NULL,
    ADD COLUMN `can_read` TINYINT(1) NULL DEFAULT NULL,
    ADD COLUMN `can_update` TINYINT(1) NULL DEFAULT NULL,
    ADD COLUMN `can_delete` TINYINT(1) NULL DEFAULT NULL;
```

File: `database/sql/2026_11_13_role_feature_crud.sql`. Jika kolom sudah ada,
lewati ADD terkait. Alternatif migration yang memeriksa kolom yang sudah ada:

```sh
php artisan migrate --path=database/migrations/2026_11_13_000003_add_crud_to_role_feature_permissions.php --force
php artisan optimize:clear
```

Gunakan SQL **atau** migration, bukan keduanya. Tabel awal tetap disiapkan melalui
`2026_11_10_000001_create_role_feature_permissions.php` atau SQL tabel awal.
Kolom nullable sengaja memakai NULL agar izin `is_allowed` lama tetap berlaku
hingga Owner menyimpan matriks baru. Modul tambahan mewarisi izin induknya jika
belum memiliki baris: Approval/Check-in/Pembayaran Membership dari `membership`,
versi PT dari `pt`. Setelah matriks baru disimpan, setiap aksi disimpan eksplisit;
checkbox yang tidak dicentang ditolak. Data cabang/pencatat diambil dari akun login.
Penyimpanan ditolak sebelum kolom CRUD tersedia agar tidak terjadi penyimpanan
setengah aturan. Migration tidak mengubah data transaksi atau absensi.
