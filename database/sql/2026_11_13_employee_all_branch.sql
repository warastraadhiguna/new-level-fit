-- Run once on the shared attendance / face_enrollment database.
-- Skip this ALTER if all_branch already exists.
ALTER TABLE `employees`
    ADD COLUMN `all_branch` TINYINT(1) NOT NULL DEFAULT 0;
