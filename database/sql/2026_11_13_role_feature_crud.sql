-- Run once on the MAIN management database (role_feature_permissions / users).
-- NULL retains existing is_allowed behavior until Owner saves CRUD settings.
-- Skip ADD clauses for columns that already exist.
ALTER TABLE `role_feature_permissions`
    ADD COLUMN `can_create` TINYINT(1) NULL DEFAULT NULL,
    ADD COLUMN `can_read` TINYINT(1) NULL DEFAULT NULL,
    ADD COLUMN `can_update` TINYINT(1) NULL DEFAULT NULL,
    ADD COLUMN `can_delete` TINYINT(1) NULL DEFAULT NULL;
