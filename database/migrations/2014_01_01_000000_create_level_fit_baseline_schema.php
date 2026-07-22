<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Baseline snapshot generated from the live MySQL schema on 2026-07-22.
     *
     * This migration intentionally uses the original MySQL DDL so legacy column
     * types, collations, index names, and foreign-key actions remain exact. All
     * tables are normalized to InnoDB.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('The Level Fit baseline schema requires MySQL.');
        }

        // Laravel creates this table before running migrations; normalize its
        // engine because the legacy MySQL server defaults to MyISAM.
        DB::statement('ALTER TABLE migrations ENGINE=InnoDB');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date_time` varchar(250) NOT NULL,
  `full_name` varchar(250) NOT NULL,
  `appointment_date` varchar(250) NOT NULL,
  `appointment_code` varchar(150) NOT NULL,
  `phone_number` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `description` text,
  `status` varchar(250) NOT NULL,
  `fc_id` int(10) NOT NULL,
  `cs_id` int(10) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_cus_ser_id` (`cs_id`),
  KEY `FK_fit_cons_id` (`fc_id`),
  CONSTRAINT `FK_cus_ser_id` FOREIGN KEY (`cs_id`) REFERENCES `customer_services` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `branch_stores` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(45) NOT NULL,
  `admin_logo` varchar(200) DEFAULT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `is_payment_strict` tinyint(1) DEFAULT '1',
  `type` varchar(10) NOT NULL DEFAULT 'both',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `check_in_members` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned DEFAULT NULL,
  `member_registration_id` int(10) NOT NULL,
  `check_in_time` varchar(50) NOT NULL,
  `check_out_time` varchar(50) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `check_in_member_fk_user_id` (`user_id`),
  KEY `member_registration_fk_check_in` (`member_registration_id`),
  KEY `FK_check_in_members_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_check_in_members_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `check_in_member_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `member_registration_fk_check_in` FOREIGN KEY (`member_registration_id`) REFERENCES `member_registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `check_in_trainer_sessions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned DEFAULT NULL,
  `trainer_session_id` int(10) DEFAULT NULL,
  `check_in_time` varchar(250) DEFAULT NULL,
  `check_out_time` varchar(50) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `pt_id` int(10) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `check_in_trainer_sessions_fk_user_id` (`user_id`),
  KEY `trainer_session_fk_check_in` (`trainer_session_id`),
  KEY `pt_check_in_fk` (`pt_id`),
  KEY `FK_check_in_trainer_sessions_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_check_in_trainer_sessions_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `check_in_trainer_sessions_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pt_check_in_fk` FOREIGN KEY (`pt_id`) REFERENCES `personal_trainers` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `trainer_session_fk_check_in` FOREIGN KEY (`trainer_session_id`) REFERENCES `trainer_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `class_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_schedule_id` bigint(20) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `member_id` int(10) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `phone` varchar(45) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_class_details_class_schedules` (`class_schedule_id`),
  KEY `FK_class_details_users` (`user_id`),
  KEY `FK_class_details_members` (`member_id`),
  CONSTRAINT `FK_class_details_class_schedules` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`id`),
  CONSTRAINT `FK_class_details_members` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `FK_class_details_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `class_instructors` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(250) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `email` varchar(250) NOT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  `last_login_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remember_token` varchar(200) DEFAULT NULL,
  `description` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `class_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_session_id` int(10) unsigned DEFAULT NULL,
  `class_instructor_id` int(10) NOT NULL,
  `branch_store_id` smallint(5) unsigned DEFAULT '1',
  `name` varchar(250) NOT NULL,
  `note` text,
  `price` decimal(10,0) NOT NULL,
  `capacity` smallint(5) unsigned NOT NULL,
  `real_capacity` smallint(5) unsigned NOT NULL DEFAULT '0',
  `class_date` date DEFAULT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Index_unique` (`class_session_id`,`class_date`),
  KEY `FK_class_schedules_class_instructors` (`class_instructor_id`),
  KEY `FK_class_schedules_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_class_schedules_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `FK_class_schedules_class_instructors` FOREIGN KEY (`class_instructor_id`) REFERENCES `class_instructors` (`id`),
  CONSTRAINT `FK_class_schedules_class_session` FOREIGN KEY (`class_session_id`) REFERENCES `class_sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `class_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_instructor_id` int(11) NOT NULL,
  `branch_store_id` smallint(5) unsigned NOT NULL DEFAULT '1',
  `name` varchar(250) NOT NULL,
  `note` text,
  `price` decimal(10,0) NOT NULL,
  `capacity` smallint(5) unsigned NOT NULL,
  `day` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_class_sessions_class_instructors` (`class_instructor_id`),
  KEY `FK_class_sessions_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_class_sessions_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `FK_class_sessions_class_instructors` FOREIGN KEY (`class_instructor_id`) REFERENCES `class_instructors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `customer_pos_services` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(250) NOT NULL,
  `gender` varchar(250) NOT NULL,
  `club` varchar(250) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `customer_services` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(250) NOT NULL,
  `gender` varchar(250) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `fitness_consultants` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(250) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `role` varchar(25) DEFAULT 'Fitness Consultant',
  `gender` varchar(250) NOT NULL,
  `address` text,
  `description` text,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fitness_consultants_user_id_fk` (`user_id`),
  CONSTRAINT `fitness_consultants_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date_time` varchar(250) NOT NULL,
  `full_name` varchar(250) NOT NULL,
  `guest_code` varchar(150) NOT NULL,
  `phone_number` varchar(250) NOT NULL,
  `email` varchar(250) DEFAULT NULL,
  `address` text,
  `source` varchar(250) NOT NULL,
  `fc_id` int(10) NOT NULL,
  `cs_id` int(10) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fit_con_id` (`fc_id`),
  KEY `cust_serv_id` (`cs_id`),
  CONSTRAINT `cust_serv_id` FOREIGN KEY (`cs_id`) REFERENCES `customer_services` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `leave_days` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `leave_day_continue_id` int(5) DEFAULT NULL,
  `member_registration_id` int(5) NOT NULL,
  `submission_date` datetime DEFAULT NULL,
  `price` int(10) NOT NULL,
  `days` int(10) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_leave_days_leave_days` (`leave_day_continue_id`),
  KEY `fk_member_registration_id` (`member_registration_id`),
  CONSTRAINT `fk_leave_days_leave_days` FOREIGN KEY (`leave_day_continue_id`) REFERENCES `leave_days` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_registration_id` FOREIGN KEY (`member_registration_id`) REFERENCES `member_registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `member_package_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `package_category_name` varchar(250) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `member_package_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `package_type_name` varchar(250) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `member_packages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned NOT NULL DEFAULT '1',
  `package_name` varchar(250) NOT NULL,
  `days` varchar(10) NOT NULL,
  `package_price` int(250) NOT NULL,
  `admin_price` int(250) NOT NULL,
  `description` text,
  `status` varchar(20) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `is_all_club` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_user_id_member_packages` (`user_id`),
  KEY `FK_member_packages_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_member_packages_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `FK_user_id_member_packages` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `member_registration_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_registration_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `method_payment_id` int(10) unsigned NOT NULL DEFAULT '6',
  `value` int(10) unsigned NOT NULL,
  `note` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_member_registration_payments_users` (`user_id`),
  KEY `FK_member_registration_payments_member_registration` (`member_registration_id`),
  CONSTRAINT `FK_member_registration_payments_member_registration` FOREIGN KEY (`member_registration_id`) REFERENCES `member_registrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_member_registration_payments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `member_registrations` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `member_id` int(10) NOT NULL,
  `member_package_id` int(10) NOT NULL,
  `package_price` int(250) NOT NULL,
  `admin_price` int(250) NOT NULL,
  `start_date` datetime NOT NULL,
  `payment_deadline` tinyint(4) DEFAULT NULL,
  `days` int(5) DEFAULT NULL,
  `old_days` int(3) DEFAULT '0',
  `method_payment_id` int(10) NOT NULL,
  `fc_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_method_payment_id` (`method_payment_id`),
  KEY `FK_member_package_id` (`member_package_id`),
  KEY `member_user_id_fk` (`user_id`),
  KEY `FK_member_registrations_members` (`member_id`),
  KEY `fc_user_fk` (`fc_id`),
  CONSTRAINT `FK_member_registrations_members` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fc_user_fk` FOREIGN KEY (`fc_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `member_package_fk_member` FOREIGN KEY (`member_package_id`) REFERENCES `member_packages` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `member_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `payment_method_fk_member` FOREIGN KEY (`method_payment_id`) REFERENCES `method_payments` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `members` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned DEFAULT '1',
  `full_name` varchar(250) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `member_code` varchar(250) DEFAULT NULL,
  `card_number` varchar(50) DEFAULT NULL,
  `gender` varchar(250) DEFAULT NULL,
  `born` datetime DEFAULT NULL,
  `phone_number` varchar(250) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ig` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `ec_name` varchar(50) DEFAULT NULL,
  `address` text,
  `photos` varchar(250) DEFAULT NULL,
  `small_photos` varchar(250) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `fc_candidate_id` int(10) unsigned DEFAULT NULL,
  `cancellation_note` text,
  `id_code_count` tinyint(3) NOT NULL DEFAULT '0',
  `lo_start` datetime DEFAULT NULL,
  `lo_end` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lo_is_used` tinyint(2) NOT NULL DEFAULT '0',
  `lo_start_date` datetime DEFAULT NULL,
  `lo_days` tinyint(2) NOT NULL DEFAULT '30',
  `lo_pt_by` tinyint(2) DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  `last_login_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remember_token` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_code` (`member_code`),
  UNIQUE KEY `card_number` (`card_number`),
  UNIQUE KEY `phone_number` (`phone_number`),
  KEY `member_fk_member_regisrations` (`full_name`),
  KEY `mbr_foreign_key_fc` (`fc_candidate_id`),
  KEY `FK_members_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_members_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `mbr_foreign_key_fc` FOREIGN KEY (`fc_candidate_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `method_payments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `personal_trainers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned DEFAULT '1',
  `full_name` varchar(250) NOT NULL,
  `phone_number` varchar(250) DEFAULT NULL,
  `role` varchar(250) NOT NULL DEFAULT 'Personal Trainer',
  `gender` varchar(250) NOT NULL,
  `address` text,
  `description` text,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_fk_personal_trainers` (`user_id`),
  KEY `FK_personal_trainers_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_personal_trainers_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `user_id_fk_personal_trainers` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pt_leave_days` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `trainer_session_id` int(10) NOT NULL,
  `submission_date` datetime DEFAULT NULL,
  `price` int(10) NOT NULL,
  `days` int(10) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pt_leave_days_trainer_session_id_index` (`trainer_session_id`),
  CONSTRAINT `pt_leave_days_trainer_session_id_fk` FOREIGN KEY (`trainer_session_id`) REFERENCES `trainer_sessions` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `trainer_package_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `package_type_name` varchar(250) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `trainer_packages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned NOT NULL DEFAULT '1',
  `package_name` varchar(250) NOT NULL,
  `number_of_session` int(250) NOT NULL,
  `days` varchar(250) NOT NULL,
  `package_price` int(250) NOT NULL,
  `admin_price` int(250) NOT NULL,
  `description` text,
  `status` varchar(20) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_user_id_trainer_packages` (`user_id`),
  KEY `FK_trainer_packages_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_trainer_packages_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `FK_user_id_trainer_packages` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `trainer_session_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trainer_session_id` int(10) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `method_payment_id` int(10) unsigned NOT NULL DEFAULT '6',
  `value` int(10) unsigned NOT NULL,
  `note` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_trainer_session_payments_users` (`user_id`),
  KEY `FK_trainer_session_payments_trainer_session` (`trainer_session_id`),
  CONSTRAINT `FK_trainer_session_payments_trainer_session` FOREIGN KEY (`trainer_session_id`) REFERENCES `trainer_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_trainer_session_payments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `trainer_sessions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `member_id` int(10) NOT NULL,
  `branch_store_id` smallint(5) unsigned NOT NULL DEFAULT '1',
  `trainer_id` int(10) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `trainer_package_id` int(10) NOT NULL,
  `days` int(100) DEFAULT NULL,
  `old_days` int(3) DEFAULT '0',
  `package_price` int(250) NOT NULL,
  `admin_price` int(250) NOT NULL,
  `payment_deadline` tinyint(4) DEFAULT '0',
  `number_of_session` int(50) NOT NULL,
  `method_payment_id` int(10) NOT NULL,
  `fc_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_package_fk_id` (`trainer_package_id`),
  KEY `FK_user_id` (`user_id`),
  KEY `trainer_fk_id` (`trainer_id`),
  KEY `trainer_fk_member_id` (`member_id`),
  KEY `method_payment_id_fk_fc` (`method_payment_id`),
  KEY `fc_user_ts_fk` (`fc_id`),
  KEY `FK_trainer_sessions_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_trainer_sessions_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `FK_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fc_user_ts_fk` FOREIGN KEY (`fc_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `method_payment_id_fk_fc` FOREIGN KEY (`method_payment_id`) REFERENCES `method_payments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `trainer_fk_id` FOREIGN KEY (`trainer_id`) REFERENCES `personal_trainers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `trainer_fk_member_id` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `trainer_package_fk_id` FOREIGN KEY (`trainer_package_id`) REFERENCES `trainer_packages` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `trainer_transaction_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `transaction_name` varchar(250) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `trainers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `transaction_type_id` int(10) NOT NULL,
  `member_id` int(10) NOT NULL,
  `trainer_id` int(10) NOT NULL,
  `trainer_package_id` int(10) NOT NULL,
  `method_payment_id` int(10) NOT NULL,
  `fc_id` int(10) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `description` text,
  `photos` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_transaction_type_id` (`transaction_type_id`),
  KEY `FK_member_id` (`member_id`),
  KEY `FK_trainer_package_id` (`trainer_package_id`),
  KEY `FK_fc_id` (`fc_id`),
  KEY `FK_method_payments_id` (`method_payment_id`),
  KEY `FK_trainer_id` (`trainer_id`),
  KEY `user_fk_id` (`user_id`),
  CONSTRAINT `FK_method_payments_id` FOREIGN KEY (`method_payment_id`) REFERENCES `method_payments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_trainer_id` FOREIGN KEY (`trainer_id`) REFERENCES `personal_trainers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_trainer_package_id` FOREIGN KEY (`trainer_package_id`) REFERENCES `trainer_packages` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_transaction_type_id` FOREIGN KEY (`transaction_type_id`) REFERENCES `trainer_transaction_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `member_fk_id` FOREIGN KEY (`member_id`) REFERENCES `member_registrations` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `user_fk_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branch_store_id` smallint(5) unsigned DEFAULT '1',
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `FK_users_branch_stores` (`branch_store_id`),
  CONSTRAINT `FK_users_branch_stores` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            // CREATE IF NOT EXISTS does not change engines on legacy tables.
            DB::statement('ALTER TABLE cache ENGINE=InnoDB');
            DB::statement('ALTER TABLE sessions ENGINE=InnoDB');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * This baseline can be applied safely to an existing legacy database because
     * every CREATE statement uses IF NOT EXISTS. It is intentionally irreversible
     * to prevent a rollback from deleting pre-existing production tables.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
