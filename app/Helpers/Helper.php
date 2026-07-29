<?php

function formatRupiah($nominal, $prefix = null)
{
    $prefix = $prefix ? $prefix : 'Rp. ';
    return $prefix . number_format($nominal, 0, ',', '.');
}

function DateFormat($date, $format = 'Y-MM-DD')
{
    return \Carbon\Carbon::parse($date)->isoFormat($format);
}

function ConvertToDate($string)
{
    return \Carbon\Carbon::parse($string);
}

function DateDiff($oldDate, $newDate, $startZero = false)
{
    $oldDate = \Carbon\Carbon::parse($oldDate);
    $newDate = \Carbon\Carbon::parse($newDate);
    if ($startZero) {
        $oldDate->hour = 0;
        $oldDate->minute = 0;
        $oldDate->second = 0;

        $newDate->hour = 0;
        $newDate->minute = 0;
        $newDate->second = 0;
    }

    return $oldDate->diffInDays($newDate);
}

function BirthdayDiff($bornDate)
{
    $birthday =
        \Carbon\Carbon::parse($bornDate)->tz('Asia/Jakarta');
    $birthday->year(date('Y'));
    $birthday->hour = 0;
    $birthday->minute = 0;
    $birthday->second = 0;
    $nowDate = \Carbon\Carbon::now()->tz('Asia/Jakarta');

    $nowDate->hour = 0;
    $nowDate->minute = 0;
    $nowDate->second = 0;
    // dd($birthday);
    // Hitung selisih hari antara hari ini dan ulang tahun berikutnya
    $daysUntilNextBirthday = $nowDate->diff($birthday);

    return $daysUntilNextBirthday->invert == 0 ? $daysUntilNextBirthday->days : -1;
}


function PaymentExpiredDateDiff($startDateString)
{
    $startDate =
        \Carbon\Carbon::parse($startDateString)->tz('Asia/Jakarta');
    $startDate->hour = 0;
    $startDate->minute = 0;
    $startDate->second = 0;

    $nowDate = \Carbon\Carbon::now()->tz('Asia/Jakarta');
    $nowDate->hour = 0;
    $nowDate->minute = 0;
    $nowDate->second = 0;

    $dayDiff = $nowDate->diff($startDate);

    return $dayDiff;
}

function BranchStorePaymentIsStrict(?int $branchStoreId = null): bool
{
    $branchStoreId = $branchStoreId ?: optional(auth()->user())->branch_store_id;

    if (!$branchStoreId) {
        return true;
    }

    static $strictByBranch = [];

    if (!array_key_exists($branchStoreId, $strictByBranch)) {
        $value = \App\Models\BranchStore::whereKey($branchStoreId)->value('is_payment_strict');
        $strictByBranch[$branchStoreId] = $value === null ? true : (bool) $value;
    }

    return $strictByBranch[$branchStoreId];
}

function NormalizePaymentDeadline($paymentDeadline, $paidAmount, $totalPrice, ?int $branchStoreId = null): int
{
    if ((int) $paidAmount >= (int) $totalPrice || BranchStorePaymentIsStrict($branchStoreId)) {
        return 0;
    }

    return max(0, (int) ($paymentDeadline ?? 0));
}

function BranchStoreDiscountIsEnabled(string $type, ?int $branchStoreId = null): bool
{
    $branchStoreId = $branchStoreId ?: optional(auth()->user())->branch_store_id;
    if (!$branchStoreId) {
        return false;
    }

    $column = $type === 'trainer' ? 'trainer_discount_enabled' : 'member_discount_enabled';

    return (bool) \App\Models\BranchStore::whereKey($branchStoreId)->value($column);
}

function NormalizeSalesDiscount($value, string $type, ?int $branchStoreId = null): int
{
    if (!BranchStoreDiscountIsEnabled($type, $branchStoreId)) {
        return 0;
    }

    return max(0, (int) str_replace(['.', ','], '', (string) $value));
}


function NowDate($format = 'Y-MM-DD')
{
    return  $nowDate = \Carbon\Carbon::now()->tz('Asia/Jakarta')->isoFormat($format);
}

function MembershipHasOneClubBranchRestriction($membership, $branchStoreId)
{
    if (!$membership) {
        return false;
    }

    return (string) ($membership->is_all_club ?? '1') === '0'
        && (int) ($membership->member_package_branch_store_id ?? 0) !== (int) $branchStoreId;
}

function MembershipOneClubRestrictionMessage($memberName, $activity = 'akses')
{
    return $memberName . ' memiliki membership One Club, tidak bisa ' . $activity . ' di cabang ini';
}

function GetLatestNonExpiredMembershipAccess($memberId = '', $cardNumber = '', $branchStoreId = null)
{
    $sql = "SELECT
            m.id AS member_id,
            m.full_name AS member_name,
            m.member_code,
            m.card_number,
            mr.id AS member_registration_id,
            mp.is_all_club,
            mp.branch_store_id AS member_package_branch_store_id
        FROM members m
        JOIN member_registrations mr ON mr.member_id = m.id
        JOIN member_packages mp ON mp.id = mr.member_package_id
        LEFT JOIN (
            SELECT member_registration_id, SUM(days) AS total_days
            FROM leave_days
            GROUP BY member_registration_id
        ) ld ON ld.member_registration_id = mr.id
        WHERE DATE(mr.start_date) <= CURDATE()
            AND DATE(DATE_ADD(mr.start_date, INTERVAL (mr.days + IFNULL(ld.total_days, 0)) DAY)) >= CURDATE()";

    $bindings = [];

    if ($branchStoreId) {
        $sql .= " AND (mp.is_all_club = 1 OR mp.branch_store_id = ?)";
        $bindings[] = $branchStoreId;
    }

    if ($memberId) {
        $sql .= " AND m.id = ?";
        $bindings[] = $memberId;
    }

    if ($cardNumber) {
        $sql .= " AND m.card_number = ?";
        $bindings[] = $cardNumber;
    }

    $sql .= " ORDER BY mp.is_all_club DESC, mr.start_date DESC, mr.id DESC";

    $memberships = \Illuminate\Support\Facades\DB::select($sql, $bindings);

    return $memberships[0] ?? null;
}

function GetAccessibleNonExpiredMembersForBranch($branchStoreId)
{
    $sql = "SELECT
            m.id,
            m.full_name,
            m.member_code,
            m.phone_number
        FROM members m
        WHERE EXISTS (
            SELECT 1
            FROM member_registrations mr
            JOIN member_packages mp ON mp.id = mr.member_package_id
            LEFT JOIN (
                SELECT member_registration_id, SUM(days) AS total_days
                FROM leave_days
                GROUP BY member_registration_id
            ) ld ON ld.member_registration_id = mr.id
            WHERE mr.member_id = m.id
                AND DATE(mr.start_date) <= CURDATE()
                AND DATE(DATE_ADD(mr.start_date, INTERVAL (mr.days + IFNULL(ld.total_days, 0)) DAY)) >= CURDATE()
                AND (mp.is_all_club = 1 OR mp.branch_store_id = ?)
        )
        ORDER BY m.full_name";

    return \Illuminate\Support\Facades\DB::select($sql, [$branchStoreId]);
}
