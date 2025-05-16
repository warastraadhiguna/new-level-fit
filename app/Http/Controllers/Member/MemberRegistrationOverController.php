<?php

namespace App\Http\Controllers\Member;

use App\Exports\MemberExpiredExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class MemberRegistrationOverController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new MemberExpiredExport(), 'member-expired, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $memberRegistrationsOver = Member::select(
            'b.id as mr_id',
            'a.id',
            'a.full_name',
            'a.member_code',
            'a.photos',
            'b.days',
            'b.start_date',
            'max_end_date',
            'total_package_price',
            'total_admin_price',
            'c.registered_member_id'
        )
            ->from('members as a')
            ->join(DB::raw('(select a.id as id_max, b.id, b.days, b.start_date, max(DATE_ADD(b.start_date, INTERVAL b.days DAY)) as max_end_date, sum(package_price) as total_package_price,
            DATE_ADD(b.start_date, INTERVAL b.days DAY) as expired_date_date,                
            sum(admin_price) as total_admin_price from members a inner join member_registrations b on a.id=b.member_id
                            where DATE_ADD(b.start_date, INTERVAL b.days DAY) < now() group by a.id, b.id, b.days, b.start_date) as b'), function ($join) {
                $join->on('a.id', '=', 'b.id_max');
            })
            ->leftJoin(DB::raw('(select distinct member_id as registered_member_id from member_registrations where DATE_ADD(start_date, INTERVAL days DAY) >= now()) as c'), function ($join) {
                $join->on('a.id', '=', 'c.registered_member_id');
            })
            ->whereNull('c.registered_member_id')
            ->where('b.days', '>', '1')
            ->get();

        $data = [
            'title'                     => 'Member Expired List',
            'memberRegistrationsOver'   => $memberRegistrationsOver,
            'content'                   => 'admin/member-registration-over/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function oneVisitExpired()
    {
        $memberRegistrationsOver = Member::select(
            'b.id as mr_id',
            'a.id',
            'a.full_name',
            // 'start_date',
            'max_end_date',
            'total_package_price',
            'total_admin_price',
            'c.registered_member_id'
        )
            ->from('members as a')
            ->join(DB::raw('(select a.id as id_max, b.id, max(DATE_ADD(b.start_date, INTERVAL b.days DAY)) as max_end_date, sum(package_price) as total_package_price,
                            sum(admin_price) as total_admin_price from members a inner join member_registrations b on a.id=b.member_id
                            where DATE_ADD(b.start_date, INTERVAL b.days DAY) < now() group by a.id, b.id) as b'), function ($join) {
                $join->on('a.id', '=', 'b.id_max');
            })
            ->leftJoin(DB::raw('(select distinct member_id as registered_member_id from member_registrations where DATE_ADD(start_date, INTERVAL days DAY) >= now()) as c'), function ($join) {
                $join->on('a.id', '=', 'c.registered_member_id');
            })
            ->whereNull('c.registered_member_id')
            ->where('status', 'one_day_visit')
            ->get();

        $data = [
            'title'                     => '1 Day Visit Expired',
            'memberRegistrationsOver'   => $memberRegistrationsOver,
            'content'                   => 'admin/one-visit/one-visit-expired'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function pdfReport()
    {
        $memberRegistrationsOver = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.package_price',
                'a.start_date',
                'a.admin_price',
                'a.description',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number',
                'b.photos',
                'b.gender',
                'b.nickname',
                'b.ig',
                'b.emergency_contact',
                'b.email',
                'b.born',
                'b.address',
                'c.package_name',
                'c.days',
                'e.name as method_payment_name',
                'f.full_name as staff_name',
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('"Over" as status'),
                DB::raw('SUM(a.package_price) as total_price'),
                DB::raw('SUM(a.admin_price) as admin_price')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->whereRaw('NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->groupBy(
                'a.id',
                'a.start_date',
                'a.admin_price',
                'a.description',
                'a.package_price',
                'b.full_name',
                'b.member_code',
                'b.phone_number',
                'b.photos',
                'b.gender',
                'b.nickname',
                'b.ig',
                'b.emergency_contact',
                'b.email',
                'b.born',
                'b.address',
                'c.package_name',
                'c.days',
                'e.name',
                'f.full_name',
                'expired_date',
                'status'
            )
            ->get();

        $pdf = Pdf::loadView('admin/member-registration-over/pdf', [
            'memberRegistrationsOver'   => $memberRegistrationsOver,
        ])->setPaper('a4', 'landscape');
        return $pdf->stream('member-expired-report.pdf');
    }

    public function excel()
    {
        return Excel::download(new MemberExpiredExport(), 'member-expired.xlsx');
    }
}
