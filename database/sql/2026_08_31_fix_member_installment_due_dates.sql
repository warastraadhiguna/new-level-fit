-- Audit data yang tanggal jatuh temponya tidak sesuai urutan bulan kontrak.
SELECT
    i.id AS installment_id,
    i.member_registration_id,
    mr.start_date,
    i.month_number,
    i.type,
    i.due_date AS current_due_date,
    DATE_ADD(DATE(mr.start_date), INTERVAL (i.month_number - 1) MONTH) AS expected_due_date,
    i.status
FROM member_registration_installments AS i
INNER JOIN member_registrations AS mr
    ON mr.id = i.member_registration_id
WHERE i.due_date <> DATE_ADD(DATE(mr.start_date), INTERVAL (i.month_number - 1) MONTH)
ORDER BY i.member_registration_id, i.month_number;

-- Koreksi seluruh bulan yang salah, termasuk deposit bulan ke-12 dan jadwal
-- yang tidak lagi sesuai karena start_date registrasi pernah berubah.
UPDATE member_registration_installments AS i
INNER JOIN member_registrations AS mr
    ON mr.id = i.member_registration_id
SET
    i.due_date = DATE_ADD(DATE(mr.start_date), INTERVAL (i.month_number - 1) MONTH),
    i.updated_at = NOW()
WHERE i.due_date <> DATE_ADD(DATE(mr.start_date), INTERVAL (i.month_number - 1) MONTH);

-- Verifikasi: hasilnya harus 0.
SELECT COUNT(*) AS remaining_incorrect_rows
FROM member_registration_installments AS i
INNER JOIN member_registrations AS mr
    ON mr.id = i.member_registration_id
WHERE i.due_date <> DATE_ADD(DATE(mr.start_date), INTERVAL (i.month_number - 1) MONTH);
