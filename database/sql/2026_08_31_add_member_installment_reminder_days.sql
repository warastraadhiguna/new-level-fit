ALTER TABLE `branch_stores`
    ADD COLUMN `member_installment_reminder_days` TINYINT UNSIGNED NOT NULL DEFAULT 7
    AFTER `member_installment_enabled`;
