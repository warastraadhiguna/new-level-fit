ALTER TABLE `member_registration_payments`
    ADD COLUMN `received_amount` INT UNSIGNED NULL AFTER `value`;

ALTER TABLE `trainer_session_payments`
    ADD COLUMN `received_amount` INT UNSIGNED NULL AFTER `value`;
