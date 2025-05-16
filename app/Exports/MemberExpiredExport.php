<?php

namespace App\Exports;

use App\Models\Member\Member;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;

class MemberExpiredExport implements FromView
{
    public function view(): View
    {
        $nowTime = Carbon::now()->tz('Asia/Jakarta');
        $nowTimeString = DateFormat($nowTime, "Y-MM-DD");
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $toDate = $toDate ? $toDate : $nowTimeString;
        $fromDate = $fromDate ? $fromDate : $nowTimeString;

        $memberRegistrationsOver = Member::select(
            'a.id',
            'a.phone_number',
            'a.full_name as member_name',
            'b.id as mr_id',
            'b.start_date',
            'b.package_name',
            'b.package_price as mr_package_price',
            'b.admin_price as mr_admin_price',
            'b.description',
            'b.days as member_registration_days',
            'b.fc_full_name',
            'b.name as method_payment_name',
            'b.staff_full_name',
            'max_end_date',
            'total_package_price',
            'total_admin_price',
            'c.registered_member_id',
        )
            ->from('members as a')
            ->join(DB::raw('(select a.id as id_max, b.id, mpac.package_name, mpay.name, b.package_price, b.admin_price, b.days, b.description, b.start_date,
                    fc.full_name as fc_full_name, staff.full_name as staff_full_name, max(DATE_ADD(b.start_date, INTERVAL b.days DAY)) as max_end_date,
                    sum(b.package_price) as total_package_price,
                    sum(b.admin_price) as total_admin_price from members a 
                    inner join member_registrations b on a.id=b.member_id 
                    INNER JOIN member_packages mpac ON b.member_package_id = mpac.id
                    INNER JOIN method_payments mpay ON b.method_payment_id = mpay.id
                    LEFT JOIN fitness_consultants fc ON b.fc_id = fc.id
                    INNER JOIN users staff ON b.user_id = staff.id
                    where DATE_ADD(b.start_date, INTERVAL b.days DAY) < now() 
                    group by a.id, b.id, mpac.package_name, mpay.name, b.package_price, b.admin_price, b.days, b.description, b.start_date, fc.full_name, staff.full_name) as b'), function ($join) {
                $join->on('a.id', '=', 'b.id_max');
            })
            ->leftJoin(DB::raw('(select distinct member_id as registered_member_id from member_registrations where DATE_ADD(start_date, INTERVAL days DAY) >= now()) as c'), function ($join) {
                $join->on('a.id', '=', 'c.registered_member_id');
            })
            ->where('a.created_at', '>=', $fromDate)
            ->where('a.created_at', '<=', $toDate)
            ->whereNull('c.registered_member_id')
            ->where('b.days', '>', '1')
            ->get();

        // dd($memberRegistrationsOver);

        // $memberRegistrationsOver = Member::select(
        //     'b.id as mr_id',
        //     'a.id',
        //     'a.full_name',
        //     'a.member_code',
        //     'a.photos',
        //     'b.days',
        //     'b.start_date',
        //     'max_end_date',
        //     'total_package_price',
        //     'total_admin_price',
        //     'c.registered_member_id'
        // )
        //     ->from('members as a')
        //     ->join(DB::raw('(select a.id as id_max, b.id, b.days, b.start_date, max(DATE_ADD(b.start_date, INTERVAL b.days DAY)) as max_end_date, sum(package_price) as total_package_price,
        //     DATE_ADD(b.start_date, INTERVAL b.days DAY) as expired_date_date,                
        //     sum(admin_price) as total_admin_price from members a inner join member_registrations b on a.id=b.member_id
        //                     where DATE_ADD(b.start_date, INTERVAL b.days DAY) < now() group by a.id, b.id, b.days, b.start_date) as b'), function ($join) {
        //         $join->on('a.id', '=', 'b.id_max');
        //     })
        //     ->leftJoin(DB::raw('(select distinct member_id as registered_member_id from member_registrations where DATE_ADD(start_date, INTERVAL days DAY) >= now()) as c'), function ($join) {
        //         $join->on('a.id', '=', 'c.registered_member_id');
        //     })
        //         ->where('a.created_at', '>=', $fromDate)
        //         ->where('a.created_at', '<=', $toDate)
        //     ->whereNull('c.registered_member_id')
        //     ->where('b.days', '>', '1')
        //     ->get();

        return view('admin.member-registration-over.excel', [
            'memberRegistrations' => $memberRegistrationsOver
        ]);
    }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             // Apply background color to specific rows
    //             $event->sheet->getStyle('A2:M' . ($event->sheet->getHighestRow()))
    //                 ->applyFromArray([
    //                     'font' => ['bold' => true],
    //                     'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F5E400']],
    //                 ]);
    //         }
    //     ];
    // }
}
