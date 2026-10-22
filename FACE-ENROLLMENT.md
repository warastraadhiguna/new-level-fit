# Pendaftaran wajah di aplikasi master

> Pembaruan: satu cabang kini dapat memiliki beberapa perangkat aktif. Lihat [FACE-MULTI-DEVICE.md](FACE-MULTI-DEVICE.md) untuk SQL manual dan deployment. Penjelasan satu perangkat pada bagian historis di bawah sudah digantikan.

Halaman **GET /enroll** dan **POST /enroll** tersedia pada `new-level-fit-master`. Akses menggunakan login aplikasi yang sudah ada, middleware Management, dan hak Admin/Owner. Menu sidebar: **Staff → Pendaftaran Wajah**, di bawah **Staff List**.

- Owner dan Admin hanya dapat mendaftarkan akun aktif pada cabang akun yang sedang login (`branch_store_id`).
- Pembatasan cabang diterapkan pada GET, POST, dan pemeriksaan ulang sebelum penyimpanan. Parameter cabang dari browser tidak menentukan akses. Jika akun tidak memiliki cabang, akses ditolak.
- Pegawai berasal dari `level-fit.users` yang `deleted_at`-nya kosong.
- Nama cabang berasal dari `level-fit.branch_stores`.
- Embedding disimpan di `level-fit.face_attendance_employees`, dengan `source_user_id` dan kode `USER-{id}` yang sama dengan aplikasi Face Attendance.
- Pendaftaran melalui master langsung dapat dikenali aplikasi `face-attendance`. Tidak perlu enroll dua kali.
- Login dan data operasional master tetap memakai koneksi database utamanya. Koneksi enrollment bernama `face_enrollment` terpisah.

## Konfigurasi

Tambahkan pada `.env` master (sudah diterapkan secara lokal):

```dotenv
FACE_SERVICE_URL=http://127.0.0.1:8001
FACE_SERVICE_TIMEOUT=30
FACE_DB_DATABASE=level-fit
```

Secara default koneksi enrollment memakai host, port, username, dan password MySQL dari `DB_*`. Jika kredensial database sumber berbeda, tersedia override:

```dotenv
FACE_DB_HOST=127.0.0.1
FACE_DB_PORT=3306
FACE_DB_USERNAME=root
FACE_DB_PASSWORD=
```

Jalankan dengan PHP yang sesuai untuk aplikasi master (lokal: Wamp PHP 7.4.9):

```powershell
php artisan config:clear
php artisan migrate --path=database/migrations/2026_10_15_000001_create_face_enrollment_profiles.php
php artisan migrate --path=database/migrations/2026_10_15_000002_rename_face_attendance_tables.php
php artisan migrate --path=database/migrations/2026_10_15_000003_add_face_enrollment_enabled_to_branch_stores.php
php artisan serve --host=127.0.0.1 --port=8002
```

Migration khusus ini membuat tabel profil hanya jika belum tersedia. Tabel yang sudah dibuat aplikasi Face Attendance dipakai bersama; rollback migration sengaja mempertahankan profil/embedding karena dipakai aplikasi lain juga. Tidak menambahkan kolom pada `users` atau mengubah data akun sumber.

Python tetap service terpisah. Dari folder `face-attendance/face-service`, gunakan virtualenv dan jalankan:

```powershell
.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

Service Python menggunakan YuNet/SFace asli yang sudah tersedia di demo. Browser hanya mengirim capture ke master; komunikasi dengan Python ada di `app/Services/FaceEnrollmentService.php`.

## Cara memakai

1. Buka **http://localhost:8002/enroll**, lalu login sebagai Admin/Owner.
2. Pilih nama pegawai yang benar.
3. Izinkan kamera, klik **Ambil Foto**, lalu **Daftarkan Wajah**.
4. Status berubah menjadi **Wajah sudah terdaftar**.
5. Pendaftaran ulang meminta konfirmasi dan mengganti embedding sebelumnya.
6. Coba absensi di **http://localhost:8000/** menggunakan wajah yang sama.

Gunakan localhost atau HTTPS agar kamera dapat diakses browser. Tidak ada upload galeri. Pergantian pegawai membuang capture sebelumnya. Foto harus JPEG valid, maksimal 5 MB dan 12 megapiksel. Python harus mendeteksi tepat satu wajah. Kegagalan model/service tidak menimpa embedding lama. Foto enrollment tidak disimpan ke disk. Tidak ada liveness atau anti-spoofing.

## Validasi

```powershell
php vendor/bin/phpunit --filter FaceEnrollmentTest
```

Delapan test enrollment, 42 assertions: lolos. Test menggunakan SQLite in-memory khusus koneksi enrollment dan HTTP fake; tidak mengubah MySQL asli. Mencakup guest/role access, daftar akun aktif, pembatasan cabang pada GET/POST, simpan dan daftar ulang, akun terhapus, gambar invalid, dua wajah, respons model invalid, service mati, dan migration shared table.

Pemeriksaan aktual:

- Sumber MySQL `level-fit`: 51 akun aktif, tabel profil shared tersedia.
- Master PHP 7.4 → endpoint Python model asli: berhasil mengekstraksi embedding.
- GET dan POST `/enroll` terdaftar; pengunjung tanpa login diarahkan ke `/login`.
- JavaScript lolos pemeriksaan sintaks Node.
- Webcam nyata tetap perlu dicoba manual pada browser pengguna.

Pemeriksaan umum menemukan dua masalah bawaan di luar modul: `ExampleTest` mengharapkan HTTP 200 untuk halaman utama yang sekarang membutuhkan login (aktual 302), dan `artisan route:list` mencoba memuat `TrainerPackageTypeController` yang tidak ada. Route enrollment dan render halaman telah diperiksa langsung melalui test HTTP, tanpa bergantung pada route:list.

## Penamaan permanen

Tabel biometrik memakai `face_attendance_employees`; riwayat absensi memakai `face_attendance_records`. Migration rename memeriksa konflik nama sebelum melakukan perubahan dan mempertahankan seluruh data. Rollback tidak menjatuhkan tabel bersama. Sidebar sekarang mempunyai induk **Staff**, dengan urutan **Staff List**, lalu **Pendaftaran Wajah**.

Jalankan `php vendor/bin/phpunit --filter "FaceEnrollmentTest|FaceTableRenameTest"` untuk memeriksa enrollment, struktur submenu, rename data, foreign key, dan perlindungan terhadap konflik tabel.

Validasi perubahan nama: 14 test aplikasi absensi dan 10 test enrollment/migrasi master lolos. Seluruh baris data sebelum/sesudah rename memiliki hash identik; foto audit lama tetap HTTP 200. Tampilan absensi sudah memakai judul Face Attendance tanpa badge Demo.

## Aktivasi per cabang (default Nonaktif)

Buka **secret-branch-store → Edit cabang → Pendaftaran Wajah Pegawai → Aktif**, lalu simpan. Flag `branch_stores.face_enrollment_enabled` berada pada database utama master dan default false untuk cabang lama maupun baru. Saat Nonaktif, submenu Pendaftaran Wajah disembunyikan dan GET/POST `/enroll` menghasilkan 404; Staff List tetap tersedia. Hak Admin/Owner dan pembatasan pegawai sesuai cabang login tetap berlaku. Tidak ada cabang yang otomatis diaktifkan oleh migration.

URL enrollment lama di aplikasi Face Attendance hanya redirect ke master (`FACE_ENROLLMENT_URL`, default `http://localhost:8002/enroll`); POST enrollment di aplikasi absensi sudah dihapus agar tidak melewati login/pengaturan cabang.

Validasi perubahan pengaturan: 19 tes master terkait enrollment, pengaturan cabang, dan rename tabel lolos (98 assertions); 14 tes aplikasi absensi lolos (48 assertions). Pengujian penyimpanan pengaturan memakai database in-memory dan tidak mengubah pengaturan cabang asli.

## Perangkat absensi khusus Owner

Menu **Staff → Perangkat Absensi** (`/attendance-device`) hanya tersedia untuk Owner. Admin dan seluruh role pegawai ditolak pada GET, POST pembuatan kode, dan POST pencabutan akses. Pengelolaan perangkat tetap dapat dibuka saat enrollment nonaktif agar Owner tetap dapat mencabut HP.

Jalankan migration khusus:

```powershell
php artisan migrate --path=database/migrations/2026_10_15_000004_create_face_attendance_devices.php
```

Owner mengisi label HP, membuat kode aktivasi sekali pakai (5 menit), lalu memasukkan kode pada `/activate` aplikasi absensi di HP cabang. Kode harus dimasukkan sendiri oleh Owner dan tidak dibagikan. Penggantian token berlaku setelah kode dipakai; kode baru yang belum dipakai tidak memutus HP lama. Cabut Akses membatalkan token dan kode aktif hanya untuk cabang akun Owner saat ini. Semua parameter cabang dari browser diabaikan.

Set `FACE_ATTENDANCE_URL=https://domain-absensi/activate` pada `.env` untuk tautan aktivasi sesuai deployment. Data device disimpan pada koneksi `face_enrollment`, tabel `face_attendance_devices`; tabel ini juga dipakai aplikasi Face Attendance. Kode/token disimpan dalam bentuk hash. Tabel tidak dijatuhkan saat rollback untuk menjaga data bersama.

Aktivasi terikat pada browser/cookie, bukan identitas fisik HP. Rincian cookie, HTTPS, mode kiosk, foto privat dan pergantian HP ada pada README `face-attendance`.

## Snapshot cabang login Owner

Saat Owner membuat kode atau mencabut akses perangkat, server menyimpan `owner_id`, `owner_name`, `owner_login_branch_store_id`, `owner_login_branch_name`, dan `owner_recorded_at` (UTC) pada `face_attendance_devices`. Nilai diambil dari akun/cabang autentikasi, bukan request input. Pencatatan ini adalah **snapshot pengaturan terakhir**, bukan log setiap login Owner atau daftar seluruh peristiwa. Snapshot tetap sama saat kode ditebus di HP dan tidak berubah hanya karena akun Owner berpindah cabang atau nama cabang diganti. Halaman Perangkat Absensi menampilkan identitas dan cabang login Owner pada pengaturan terakhir.

Migration master: `2026_10_15_000005_add_owner_login_context_to_attendance_devices.php`. Aplikasi absensi juga memiliki migration idempotent `2026_10_15_000002_add_owner_login_context_to_devices.php`. Data lama yang belum memiliki snapshot dibiarkan kosong, tidak direkonstruksi dari cabang akun saat ini.
