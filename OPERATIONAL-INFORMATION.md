# Informasi pada halaman operasional

Halaman operasional hanya menampilkan informasi yang diperlukan untuk pekerjaan.
Jangan menambahkan banner tentang fitur yang dimatikan, petunjuk SQL/migration,
nama tabel/database, progres pekerjaan internal, stack trace, atau respons mentah
layanan lain. Fitur yang tidak tersedia disembunyikan dan aksesnya tetap ditolak.

Pengaturan khusus Owner tetap berada pada halaman pengaturan dengan otorisasi
yang sesuai. Status bisnis seperti pembayaran, approval, kehadiran, pegawai,
produk, dan template aktif/nonaktif tetap diperlukan dan tidak dihapus.

Error tak terduga menghasilkan pesan aman HTML/JSON tanpa query, kredensial,
nama model, path file, atau trace, termasuk saat APP_DEBUG tidak sengaja aktif.
Detail tetap dilaporkan melalui logging Laravel. Validasi formulir dan alur
autentikasi tetap mengikuti Laravel. Error 404 tidak menjelaskan konfigurasi cabang.
Kesalahan pembayaran yang diketahui menggunakan validasi field; error teknis
tidak diteruskan melalui flash message.

Pendaftaran wajah hanya meneruskan petunjuk kualitas foto dari kode error yang
dikenal. Teks mentah layanan wajah dan versi model tidak dikirim ke layar.
Aplikasi Face Attendance memiliki perubahan terpisah untuk menghilangkan skor
pencocokan, penjelasan keterbatasan verifikasi, dan detail error internal dari UI.
Data skor audit tetap tersimpan di database; tidak ada perubahan algoritma wajah.

Audit mencakup template Blade, ekspor, JavaScript milik aplikasi, pesan controller,
middleware, dan penanganan error di Master serta halaman Face Attendance Laravel.
Route demonstrasi `/tes` dihapus dari Master. Tidak ada migration/SQL tambahan.
