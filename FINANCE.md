# Role FINANCE

Owner/Admin membuat akun dari Staff → Akun Finance (/finance-accounts), pada
cabang login. Tambah, ubah/reset password dan hapus sementara memakai aturan yang
sama dengan Akun HRD: email unik, password minimal 8 karakter + konfirmasi, role
serta cabang ditentukan server. Akun tidak otomatis membuat data employees.

Finance login ke /finance, bukan dashboard operasional. Menu Finance menampilkan
Laporan Revenue jika cabang mengizinkan role tersebut. Finance dapat membaca
summary dan detail pendapatan memakai laporan yang sudah tersedia; seluruh query
memakai cabang akun login. Parameter branch_store_id dari URL tidak menggantinya.

## Izin Revenue cabang

Pada Secret Branch Store, bagian izin Laporan Omzet / Revenue menyediakan pilihan
Finance. Pilihan ALL/default lama mengizinkan Finance seperti role lain; OWNER_ONLY
atau daftar role yang tidak menyertakan FINANCE tetap menolak akses. Jika memakai
daftar role tertentu, Owner perlu mencentang Finance. Tidak ada perubahan otomatis
pada pengaturan cabang. Opsi Finance ini hanya untuk Revenue, bukan dashboard umum.

FINANCE bukan ADMIN/OWNER/HRD. Middleware web mengizinkan hanya halaman Finance,
Revenue, login dan logout. Transaksi, member, POS, pegawai/enrollment, Staff List,
akun HRD/Finance, pengaturan cabang dan perangkat absensi ditolak termasuk melalui
URL langsung. Ekspor Excel tetap Owner-only sesuai aturan sebelumnya. Tidak ada
hak menambah, mengedit, atau menghapus transaksi keuangan.

Akses aplikasi management diberikan saat akun dibuat, tanpa akses gym_landing.
Pencabutan akses management menyebabkan logout; role tidak dapat mengganti cabang
atau menaikkan hak sendiri. Akun HRD/Finance berbagi controller dasar dan form
akun, dengan pembatasan role/cabang masing-masing; role HRD tetap berjalan seperti
sebelumnya.

## Database dan deployment

Tidak ada migration atau SQL perubahan schema: users.role dalam baseline proyek
adalah VARCHAR(255), dan izin Revenue sudah disimpan pada field JSON/text yang
ada. Buat akun melalui menu agar password di-hash dan izin management tersimpan.
Tidak ada akun bawaan atau password default.

Jika perlu memeriksa skema database utama server:

```sql
SHOW COLUMNS FROM users LIKE 'role';
```

Deploy hanya aplikasi utama dan bersihkan cache config/route/view. Tidak ada
perubahan aplikasi absensi, Python, ataupun database bersama face_enrollment.
