-- Jalankan satu kali pada database yang tidak menggunakan Laravel migration.
-- Nilai ["ALL"] mempertahankan perilaku lama: informasi keuangan terlihat oleh semua role.

ALTER TABLE `branch_stores`
    ADD COLUMN `dashboard_finance_visible_roles` TEXT NULL;

UPDATE `branch_stores`
SET `dashboard_finance_visible_roles` = '["ALL"]'
WHERE `dashboard_finance_visible_roles` IS NULL
   OR TRIM(`dashboard_finance_visible_roles`) = '';
