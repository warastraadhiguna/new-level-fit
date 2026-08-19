-- Jalankan satu kali pada database yang tidak menggunakan Laravel migration.
-- Default 1 mempertahankan aturan lama: booking dibuka sejak H-1.

ALTER TABLE `branch_stores`
    ADD COLUMN `class_booking_advance_days` TINYINT UNSIGNED NOT NULL DEFAULT 1;
