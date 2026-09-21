<?php

namespace App\Http\Controllers\Member;

use App\Exports\MemberExpiredExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class MemberRegistrationOverController extends Controller
{
    public function index()
    {
        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new MemberExpiredExport(), 'member-expired.xlsx');
        }

        $memberRegistrationsOver = MemberRegistration::expiredRegistrations(
            '',
            Auth::user()->branch_store_id
        )->get();

        $data = [
            'title'                     => 'Member Expired List',
            'memberRegistrationsOver'   => $memberRegistrationsOver,
            'content'                   => 'admin/member-registration-over/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }



    public function pdfReport()
    {
        $memberRegistrationsOver = DB::table('member_registrations as a')
            ->where('a.days', '>', 1)
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
            ->where(function ($query) {
                $query->where('b.branch_store_id', Auth::user()->branch_store_id)
                    ->orWhere('c.is_all_club', 1);
            })
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
