CREATE TABLE IF NOT EXISTS `role_feature_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `branch_store_id` INT UNSIGNED NOT NULL,
    `role` VARCHAR(30) NOT NULL,
    `feature_key` VARCHAR(80) NOT NULL,
    `is_allowed` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_by` INT UNSIGNED NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_feature_permissions_branch_role_feature_unique` (`branch_store_id`, `role`, `feature_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
