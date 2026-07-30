-- Jalankan pada database bersama yang digunakan new-level-fit-master
-- dan landing-page/gym-landing-page.
--
-- Query ini aman untuk data lama:
-- semua ADMIN existing tetap mendapat akses ke kedua aplikasi.

CREATE TABLE IF NOT EXISTS `user_application_access` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `application_code` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_application_access_unique` (`user_id`, `application_code`),
    KEY `user_application_access_app_active_index` (`application_code`, `is_active`),
    CONSTRAINT `user_application_access_user_fk`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `user_application_access`
    (`user_id`, `application_code`, `is_active`, `created_at`, `updated_at`)
SELECT
    `id`, 'management', 1, NOW(), NOW()
FROM `users`
WHERE `role` = 'ADMIN';

INSERT IGNORE INTO `user_application_access`
    (`user_id`, `application_code`, `is_active`, `created_at`, `updated_at`)
SELECT
    `id`, 'gym_landing', 1, NOW(), NOW()
FROM `users`
WHERE `role` = 'ADMIN';

-- Contoh opsional jika ingin mengatur manual berdasarkan email.
-- Admin Management Only:
--
-- UPDATE `user_application_access` AS `uaa`
-- INNER JOIN `users` AS `u` ON `u`.`id` = `uaa`.`user_id`
-- SET `uaa`.`is_active` = 0,
--     `uaa`.`updated_at` = NOW()
-- WHERE `u`.`email` = 'admin@example.com'
--   AND `uaa`.`application_code` = 'gym_landing';
--
-- Berikan akses Gym Landing:
--
-- INSERT INTO `user_application_access`
--     (`user_id`, `application_code`, `is_active`, `created_at`, `updated_at`)
-- SELECT `id`, 'gym_landing', 1, NOW(), NOW()
-- FROM `users`
-- WHERE `email` = 'admin@example.com'
-- ON DUPLICATE KEY UPDATE
--     `is_active` = 1,
--     `updated_at` = NOW();
