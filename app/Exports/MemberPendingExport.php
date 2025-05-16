<?php

namespace App\Exports;

use App\Models\Member\MemberRegistration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class MemberPendingExport implements FromView
{
    public function view(): View
    {
        $nowTime = Carbon::now()->tz('Asia/Jakarta');
        $nowTimeString = DateFormat($nowTime, "Y-MM-DD");
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $toDate = $toDate ? $toDate : $nowTimeString;
        $fromDate = $fromDate ? $fromDate : $nowTimeString;

        $memberRegistrations = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.old_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'b.id as member_id',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number',
                'b.born',
                'b.photos',
                'b.gender',
                'c.package_name',
                'c.days',
                'c.package_price',
                'e.name as method_payment_name',
                'f.full_name as staff_name',
                'g.full_name as fc_name',
                'g.phone_number as fc_phone_number',
                'ld.submission_date',
                'ld.days as number_of_leave_days'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) as expired_leave_days'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) THEN "Ended" 
                    WHEN NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) THEN "Freeze" 
                    ELSE "No Leave Days" 
                END as leave_day_status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join(
                'users as f',
                'a.user_id',
                '=',
                'f.id'
            )
            ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            ->whereRaw('NOW() < a.start_date')
            ->where('a.created_at', '>=', $fromDate)
            ->where('a.created_at', '<=', $toDate)
            ->distinct()
            ->get();

        return view('admin.member-registration.excel-pending', [
            'memberRegistrations' => $memberRegistrations
        ]);
    }
}
