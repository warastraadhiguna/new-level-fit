# Shift dan absensi pegawai

Pengaturan berada di Master melalui menu bertingkat **Staff**:

- Staff List
- Attendance: Employees (`/employees`), Schedules (`/employee-schedules`),
  Day Off (`/employee-leaves`), Attendance Report (`/employee-attendance`).
- Attendance Setting: Perangkat absensi (`/attendance-device`, Owner saja),
  Shift template (`/employee-shifts`).

Pendaftaran wajah dibuka dari **Employees ? Detail ? Pendaftaran Wajah**.
Link membawa pegawai yang dipilih langsung ke halaman enrollment, dengan validasi
pegawai aktif/cabang dan izin enrollment. Tidak ada menu enrollment terpisah di sidebar.
Face Attendance tetap memakai aktivasi perangkat dan pengenalan wajah yang sudah ada.

## Pemasangan

Deploy perubahan Master **dan** repositori face-attendance. Keduanya harus menunjuk
database FACE yang sama. Jalankan satu kali SQL
`database/sql/2026_11_14_employee_attendance_management.sql` pada database tersebut,
atau migration baru dari Master:

```sh
php artisan migrate --path=database/migrations/2026_11_14_000001_create_employee_attendance_management.php --force
php artisan optimize:clear
```

Pada aplikasi `face-attendance/laravel`, jalankan `php artisan migrate --force`
dan `php artisan optimize:clear`. Migration kedua aplikasi idempotent: tabel/kolom
yang telah dibuat SQL atau aplikasi lain dilewati. Jangan mengulang ALTER mentah.
Migration Master memakai koneksi `face_enrollment`, bukan koneksi database default.
Tidak ada perubahan atau pemindahan profil wajah dan foto audit.

Samakan `ATTENDANCE_TIMEZONE=Asia/Jakarta` pada kedua aplikasi (default WIB).
Gunakan zona waktu cabang yang sama untuk seluruh perangkat dan penjadwalan;
belum ada zona waktu berbeda per cabang. Sebaiknya samakan `APP_TIMEZONE` juga.
Data historis tanpa jadwal tetap tampil dengan nilai keterlambatan nol;
tidak ada perhitungan ulang atau rekonstruksi jadwal lama.

## Pemakaian

1. Buat template Pagi 06:00–13:00 dan Siang 13:00–19:00. Tentukan toleransi menit.
   Jam akhir lebih kecil dari jam mulai berarti shift berakhir esok hari.
2. Pilih satu/beberapa pegawai, tanggal awal/akhir (maksimal 93 hari), hari dalam
   minggu, dan template. Bisa membuat jadwal Libur tanpa template.
3. Satu pegawai memiliki maksimal satu jadwal per tanggal kerja, lintas cabang.
   Bulk bersifat atomik: konflik satu pegawai/tanggal membatalkan seluruh kiriman.
   Jadwal malam tidak boleh tumpang tindih dengan shift hari berikutnya.
4. Jadwal menyimpan nama/jam/toleransi template saat dibuat. Edit/nonaktifkan template
   tidak mengubah jadwal lama. Untuk mengganti jadwal, hapus lalu buat ulang.
   Jadwal yang sudah memiliki absensi dikunci; template yang dipakai tidak bisa
   dihapus, tetapi bisa dinonaktifkan. Pegawai mobile dapat dijadwalkan dari cabang
   mana pun dan tetap boleh absen pada perangkat cabang lain.
5. Pegawai selfie untuk masuk/pulang. Waktu dari server. Absen masuk tersedia
   mulai 4 jam sebelum shift hingga akhir shift. Setelah toleransi, menit
   terlambat = floor(masuk - mulai - toleransi), minimum nol.
   Pulang awal = floor(akhir shift - pulang), minimum nol.
6. Pulang dipasangkan dengan absen masuk yang belum ditutup dalam 36 jam terakhir,
   termasuk lintas tengah malam/cabang untuk pegawai mobile. Tanggal kerja mengikuti
   absen masuk. Masuk baru ditolak jika masih ada sesi terbuka dalam batas ini.
   Sesi lama yang tak ditutup tetap terlihat sebagai Belum pulang pada rekap.
7. Tanpa jadwal, absensi lama tetap diizinkan dan ditandai Tanpa jadwal. Tidak ada
   keterlambatan/mangkir otomatis untuk hari tanpa jadwal. Hari libur terjadwal
   dan izin/cuti/sakit disetujui menolak absen masuk. Pending tidak memblokir.
8. Petugas mencatat izin/cuti/sakit atas nama pegawai; pengajuan menunggu persetujuan.
   Pemegang Update dapat menyetujui, menolak, atau membatalkan. Pengajuan disetujui
   hanya dapat dibatalkan. Pengajuan lain yang bertabrakan atau sudah ada absensi
   ditolak. Pegawai dikunci saat perubahan jadwal/persetujuan/absensi untuk mencegah
   hasil yang bertentangan. Cuti belum memakai kuota atau pemotongan saldo.
9. Rekap bisa difilter periode, pegawai, status; tersedia ringkasan dan CSV.
   Tidak hadir dihitung setelah shift berakhir, bukan saat masih menunggu jam masuk.
   Tanggal sebelum mulai kerja/setelah resign tidak dihitung. Pegawai nonaktif atau
   soft-delete tetap disertakan untuk riwayat. Durasi = masuk sampai pulang, termasuk
   istirahat; belum menghitung lembur/payroll. Mobile dengan pasangan masuk/pulang
   lintas cabang ditampilkan utuh pada cabang jadwal atau cabang yang dikunjungi.
   CSV memakai perlindungan formula untuk nama/kode yang diawali karakter spreadsheet.

## Akses

Empat fitur baru dapat diatur terpisah pada `/role-feature-access`:
Template Shift (CRUD), Jadwal Shift (Create/Read/Delete), Izin & Cuti
(Create/Read/Update; Update berarti keputusan pengajuan), Rekap (Read termasuk CSV, Delete untuk hapus absensi).
Default Owner, Admin, HRD. Role lain perlu grant eksplisit.
Semua mengikuti switch `face_enrollment_enabled` yang sudah ada di Secret Branch Store;
jika mati, fitur tidak muncul di pengaturan role/menu dan endpoint ditolak.

### Menghapus absensi

Attendance Report memiliki tombol Hapus Attendance untuk baris yang memiliki
catatan absensi. Konfirmasi menghapus seluruh pasangan masuk/pulang pada tanggal
kerja tersebut, termasuk pasangan shift malam dan mobile lintas cabang. Jadwal,
pegawai dan profil wajah tetap tersimpan. Rekap dihitung ulang dari catatan tersisa;
jika shift sudah berakhir, baris dapat berubah menjadi Tidak hadir.

Default hanya Owner boleh menghapus. Role lain harus diberi Read dan Delete
pada Rekap Kehadiran Pegawai. Izin Read lama/default tidak otomatis mengaktifkan
Delete. Endpoint membatasi pegawai/cabang sesuai visibilitas rekap. Snapshot ID
catatan diperiksa ulang dengan kunci pegawai yang sama seperti absensi wajah;
data yang sudah berubah mengharuskan pengguna memuat ulang laporan.

Penghapusan permanen dicatat pada log aplikasi (pelaku, cabang, pegawai, tanggal
kerja, snapshot catatan). Foto audit privat yang disimpan aplikasi Face Attendance
tetap berada di storage aplikasi tersebut; Master tidak menghapus file lintas aplikasi.
Tidak ada migration/SQL tambahan untuk fitur hapus ini.

Tidak ada akun pegawai self-service baru, kuota cuti, koreksi jam manual,
atau pemrosesan payroll pada perubahan ini. Kamera/perangkat asli tetap perlu
dicoba setelah deployment; tes otomatis menggunakan foto fixture dan service wajah tiruan.
