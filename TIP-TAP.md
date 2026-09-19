# Project tip-tap versi 1

Branch `tip-tap` menyediakan simulasi penghapusan dua tahap, khusus role `OWNER`.

## Penggunaan

1. Di Member List, pilih **Hapus sementara** untuk member beserta seluruh history.
2. Di Membership History atau PT History seorang member, pilih **Hapus sementara** pada registrasi yang diinginkan. Member dan registrasi lainnya tetap ada.
3. Buka menu **Tip-Tap / Tempat Sampah** untuk **Restore** atau **Hapus permanen**. Penghapusan permanen memerlukan teks `HAPUS PERMANEN` dan konfirmasi.

Hapus sementara mengeluarkan data dari tabel operasional: daftar, pencarian, akses/check-in, history, pembayaran, cicilan, laporan, ekspor, dan omzet tidak lagi membacanya. Restore mengembalikan ID, tanggal, nilai pembayaran, serta history semula, tanpa mengulang event pembuatan transaksi. Tanggal kedaluwarsa tidak diperpanjang selama data berada di tempat sampah.

Restore member sebelum registrasinya. Registrasi yang sudah dihapus terpisah sebelum member dihapus tetap berada di tempat sampah setelah member direstore. Hapus permanen member juga menghapus arsip registrasinya, termasuk foto lokal yang terkait. Hapus permanen registrasi tidak menghapus member.

## Instalasi

Gunakan database demo. Jalankan migration khusus branch ini:

```sh
php artisan migrate --path=database/migrations/2026_09_19_000002_create_tip_tap_trash.php
php artisan migrate --path=database/migrations/2026_09_19_000003_rename_tip_tap_tables.php
```

Tabel fitur ini bernama `trashes` dan `locks`. Migration pertama membuat tabel, lalu migration kedua mengganti nama lama tanpa menghapus isinya. Jika migration pertama sudah dijalankan, cukup jalankan migration kedua. Migration lama yang belum dijalankan tidak diubah oleh perintah tersebut. Sebelum kembali menggunakan branch lain pada database yang sama, restore data yang masih diperlukan. Pergantian branch Git tidak mengganti database.

## Penyimpanan dan konsistensi

Arsip baris lengkap dienkripsi menggunakan `APP_KEY` dan hanya tersedia di halaman owner. Pertahankan key tersebut agar arsip dapat direstore. Foto dipertahankan selama hapus sementara dan dihapus saat hapus permanen. Kode/kartu member di tempat sampah dicadangkan oleh model Member agar tidak dipakai kembali melalui aplikasi ini.

Operasi database memakai transaksi dan kunci untuk mencegah dua tindakan Tip-Tap bertabrakan. Restore yang berbenturan dengan data baru atau relasi yang hilang dibatalkan seluruhnya; arsip tetap tersedia. Bila membership dan PT yang memiliki freeze terkait dihapus terpisah, restore dalam urutan kebalikan penghapusan. Hapus permanen membersihkan referensi freeze terkait dalam arsip lain.

Relasi mengikuti schema aplikasi, termasuk kolom legacy `trainers.member_id` yang sebenarnya mengacu ke `member_registrations.id`. Jika menambahkan tabel relasi baru, perbarui daftar relasi di `TipTapService` dan pengujiannya. Foreign key yang tidak terpetakan akan menggagalkan penghapusan, bukan diabaikan.

Tidak ada catatan audit permanen baru setelah arsip dihapus. Penghapusan ini mencakup data aplikasi dan file lokal terkait; salinan backup database/file atau ekspor yang sudah dibuat sebelumnya berada di luar operasi ini.

## Verifikasi

```sh
php vendor/phpunit/phpunit/phpunit --filter TipTapTest
```

Tes memakai SQLite terpisah di memori, mencakup pembatasan owner, jalur hapus lama, pemulihan baris persis, laporan omzet, penghapusan permanen, foto, konflik restore, rollback, dan registrasi yang dihapus terpisah.
