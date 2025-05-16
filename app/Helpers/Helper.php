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

function NowDate($format = 'Y-MM-DD')
{
    return  $nowDate = \Carbon\Carbon::now()->tz('Asia/Jakarta')->isoFormat($format);
}