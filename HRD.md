# Akun HRD

Owner/Admin membuka Staff → Staff List → tab HRD (/staff?page=hrd) pada cabang login untuk
menambah, mengubah/reset password, atau menghapus sementara akun HRD. Email unik
terhadap semua akun termasuk yang dihapus sementara; password minimal 8 karakter
beserta konfirmasi. Cabang dan role ditentukan server, bukan nilai dari form.
Daftar akun dibatasi cabang login dan role HRD. Akun user/trainer lain tidak dapat
diubah melalui halaman ini. Akun HRD tidak otomatis menjadi employees absensi.

Tambah/edit memakai modal, tabel mengikuti format Staff List. Akun yang dihapus
sementara tersedia di Old Data untuk restore atau hapus permanen. Penghapusan
permanen ditolak jika akun masih terkait data lain. URL /hrd-accounts diarahkan
ke tab HRD. Pengelolaan akun tetap hanya oleh Owner/Admin.

Setelah login, HRD diarahkan ke /hrd. Menu HRD hanya berisi halaman awal serta
Data Pegawai dan Pendaftaran Wajah jika fitur wajah cabang aktif. HRD dapat
mengelola pegawai, tanggal lahir, dan contoh wajah pada cabang login, dengan
pembatasan pegawai aktif dan feature flag yang sama seperti Owner/Admin.

HRD bukan Admin. isAdmin tetap hanya untuk OWNER/ADMIN. Middleware web membatasi
HRD menggunakan daftar nama route yang diizinkan sehingga menu member, keuangan,
POS, staff/login lain, secret-branch-store, perubahan cabang, dan perangkat
absensi ditolak meskipun URL diketik langsung. Perangkat tetap khusus Owner.
Mengunjungi / mengarahkan HRD ke /hrd dan tidak membuka dashboard keuangan.
HRD tetap dapat logout atau membuka halaman awal saat fitur cabang dinonaktifkan.

Jika tabel user_application_access tersedia, pembuatan akun memberikan hanya
akses management. Akses Management juga diperiksa saat login dan setiap request
manajemen; pencabutan akses menyebabkan logout. Hak aplikasi lain tidak diberikan.
Jika tabel belum ada, pola kompatibilitas lama User::hasApplicationAccess tetap
berlaku. Password disimpan sebagai hash, bukan plaintext.

## Database / SQL manual

Tidak ada migration atau perubahan schema untuk role HRD: baseline proyek memakai
users.role VARCHAR(255), bukan ENUM. Tidak ada akun/password bawaan yang dibuat.
Akun login disimpan di users pada database utama aplikasi, bukan di koneksi
face_enrollment; tabel employees untuk absensi tetap terpisah.

Verifikasi pada database utama jika deployment memakai skema berbeda:

```sql
SHOW COLUMNS FROM users LIKE 'role';
```

Jika role bertipe VARCHAR seperti skema proyek, tidak perlu ALTER/INSERT manual;
buat akun lewat Staff → Staff List → tab HRD agar hash password dan akses Management tersimpan
bersama. Jangan mengubah kolom ke ENUM atau mengeksekusi ulang migration lama.

Deploy kode aplikasi utama saja, bersihkan cache route/config/view sesuai PHP
aplikasi. Tidak ada perubahan database, Python, atau aplikasi kiosk absensi.
