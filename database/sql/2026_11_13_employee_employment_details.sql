-- Run once on the shared attendance / face_enrollment database.
-- Alternative to 2026_11_13_000001_add_employment_details_to_employees.php.
-- If any columns already exist, skip their ADD COLUMN clauses.
ALTER TABLE `employees`
    ADD COLUMN `photo_path` VARCHAR(255) NULL,
    ADD COLUMN `ktp_photo_path` VARCHAR(255) NULL,
    ADD COLUMN `position_category` VARCHAR(20) NULL,
    ADD COLUMN `employment_start_date` DATE NULL,
    ADD COLUMN `resign_date` DATE NULL,
    ADD COLUMN `note` TEXT NULL;
