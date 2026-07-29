-- Jalankan satu kali pada setiap database yang tidak menggunakan Laravel migration.
-- Semua cabang lama tetap NONAKTIF karena nilai default kedua toggle adalah 0.

ALTER TABLE `branch_stores`
    ADD COLUMN `member_discount_enabled` TINYINT(1) NOT NULL DEFAULT 0
        AFTER `member_installment_cancel_days`,
    ADD COLUMN `trainer_discount_enabled` TINYINT(1) NOT NULL DEFAULT 0
        AFTER `member_discount_enabled`;

ALTER TABLE `member_registrations`
    ADD COLUMN `discount_amount` INT UNSIGNED NOT NULL DEFAULT 0
        AFTER `admin_price`;

ALTER TABLE `trainer_sessions`
    ADD COLUMN `discount_amount` INT UNSIGNED NOT NULL DEFAULT 0
        AFTER `admin_price`;
