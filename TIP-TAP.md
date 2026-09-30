# Project tip-tap versi 1

Branch `tip-tap` menyediakan simulasi penghapusan dua tahap, khusus role `OWNER`.

LGT dan One Day Visit sudah dihapus dari fitur branch ini: dashboard, menu, pilihan paket, registrasi, check-in, history, laporan, dan ekspor. URL khusus kedua fitur tersebut tidak lagi tersedia. Data lama tetap tersimpan di database, tetapi tidak ditampilkan atau dihitung dalam laporan aplikasi. Membership biasa, PT, dan PT Free tetap tersedia.

Perubahan penghapusan kedua fitur ini tidak memerlukan migration tambahan. Setelah memperbarui kode di server, jalankan `php artisan optimize:clear` menggunakan versi PHP yang sesuai untuk aplikasi agar cache route dan tampilan lama dibersihkan.

Laporan omzet demo (ringkasan, detail, dan Excel) memakai tanggal pembuatan membership/PT (`created_at`), bukan tanggal mulai paket atau tanggal pembayaran cicilan. Setiap registrasi dihitung sekali sebesar harga paket + biaya admin − diskon (minimum nol), termasuk registrasi yang belum dibayar. LGT, One Day Visit, dan PT Free tidak dihitung. POS tetap mengikuti transaksi penjualan selesai. Perubahan ini tidak mengubah tanggal atau nilai data tersimpan dan tidak memerlukan migration tambahan.

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

### SQL manual untuk membuat tabel dari awal

Untuk database yang belum memiliki kedua tabel fitur ini, SQL berikut langsung membuat nama final `trashes` dan `locks`. Hasilnya setara dengan menjalankan migration create lalu rename, sehingga bagian SQL rename di bawah tidak perlu dijalankan setelah memakai SQL ini.

```sql
CREATE TABLE `trashes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kind` VARCHAR(20) NOT NULL,
    `original_id` BIGINT UNSIGNED NOT NULL,
    `member_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `member_code` VARCHAR(255) NULL,
    `card_number` VARCHAR(255) NULL,
    `payload` LONGTEXT NOT NULL,
    `deleted_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`id`),
    KEY `trashes_member_id_index` (`member_id`),
    KEY `trashes_member_code_index` (`member_code`),
    KEY `trashes_card_number_index` (`card_number`),
    UNIQUE KEY `trashes_kind_original_id_unique` (`kind`, `original_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locks` (
    `id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `locks` (`id`) VALUES (1);
```

Setelah kedua tabel dan data awal berhasil dibuat, catat kedua migration pada tabel `migrations` Laravel yang sudah tersedia:

```sql
SET @tip_tap_create_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_19_000002_create_tip_tap_trash', @tip_tap_create_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_19_000002_create_tip_tap_trash'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_19_000003_rename_tip_tap_tables', @tip_tap_create_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_19_000003_rename_tip_tap_tables'
);
```

### SQL manual untuk migration terakhir

SQL MySQL berikut setara dengan migration `2026_09_19_000003_rename_tip_tap_tables.php`. Gunakan jika tabel `tip_tap_trash` dan `tip_tap_locks` sudah ada, sedangkan `trashes` dan `locks` belum ada:

```sql
RENAME TABLE
    `tip_tap_trash` TO `trashes`,
    `tip_tap_locks` TO `locks`;
```

Perintah ini mengganti nama tabel tanpa menghapus isinya. Di database lokal proyek ini, rename sudah dijalankan melalui migration, sehingga tidak perlu dijalankan lagi.

Jika menjalankan SQL tersebut secara manual sebagai pengganti Artisan, setelah rename berhasil jalankan SQL berikut agar Laravel mencatat migration sebagai sudah dijalankan:

```sql
SET @tip_tap_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_19_000003_rename_tip_tap_tables', @tip_tap_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_19_000003_rename_tip_tap_tables'
);
```

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

## Acuan cabang laporan omzet

Membership mengikuti `members.branch_store_id`, sesuai dashboard. PT mengikuti `trainer_sessions.branch_store_id`; jika NULL pada data lama, gunakan cabang member. Cabang akun staf tidak menentukan omzet. Ringkasan, detail, dan Excel memakai aturan yang sama. Cabang member belum merupakan snapshot historis: jika member dipindahkan, omzet membership ikut cabang member saat ini.

## Prompt perbaikan untuk branch master

Salin prompt berikut saat bekerja di branch master:

```text
Perbaiki penentuan cabang laporan revenue pada branch master di project new-level-fit-master.

Masalah: membership member Supriyadi tidak muncul di laporan Supriyadi karena query mengutamakan users.branch_store_id milik staf. Akun staf dapat berada di cabang lain atau pindah cabang; perubahan itu tidak boleh memindahkan omzet.

Periksa implementasi dan skema master terlebih dahulu. Terapkan acuan cabang:
1. Membership menggunakan cabang registrasi yang tersimpan jika memang tersedia dan merupakan cabang transaksi. Jika registrasi belum memiliki kolom tersebut, gunakan members.branch_store_id, sesuai dashboard saat ini.
2. PT menggunakan trainer_sessions.branch_store_id. Untuk nilai NULL pada data lama, gunakan members.branch_store_id.
3. Jangan gunakan cabang akun staf sebagai penentu atau fallback omzet.
4. Jika master masih menggunakan payment.branch_store_id, audit asal pengisiannya. Nilai yang berasal dari cabang staf/backfill staf tidak boleh mengalahkan acuan registrasi/member tersebut. Jangan mengubah data tersimpan secara massal.
5. Pastikan ringkasan, detail, dan ekspor Excel memakai filter cabang yang sama; pertahankan pembatasan akses cabang.
6. Pertahankan aturan tanggal pembayaran/cicilan, nominal omzet, POS, dan kategori fitur yang berlaku di master. Jangan menyalin aturan khusus demo tip-tap (tanggal dibuat registrasi, nilai penuh paket, atau penghapusan LGT/One Day Visit).
7. Tambahkan tes regresi: member cabang A dibuat staf cabang B, staf pindah cabang, dan PT memiliki cabang berbeda dari member. Transaksi harus masuk hanya ke cabang sesuai aturan di atas tanpa duplikasi.
8. Jalankan tes relevan dan laporkan hasil serta batasan historis ketika membership masih mengikuti cabang member saat ini. Jangan membuat migration, commit, push, atau menghapus data tanpa permintaan tambahan.
```

## Hapus permanen semua

Owner dapat membuka **Hapus permanen semua** di Tempat Sampah, mengetik **HAPUS SEMUA PERMANEN**, lalu menyetujui konfirmasi. Tindakan mencakup seluruh arsip di semua halaman dan cabang beserta foto arsip; data aktif tidak dihapus. Tidak memerlukan migration tambahan.
