<?php

namespace App\Http\Controllers\Report;

use App\Exports\MemberCheckInReportExport;
use App\Exports\MemberPTCheckInReportExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
class ReportController extends Controller
{
    public function reportMemberCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $memberId   = Request()->input('memberId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $member = Member::all();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        if ($memberId) {
            $results = DB::table('members')
                ->select(
                    'cim.id as cim_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cim.check_in_time',
                    'cim.check_out_time',
                    'branch_stores.name as branch_store_name'
                )
                ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
                ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
                ->join('branch_stores', 'members.branch_store_id', '=', 'branch_stores.id')
                ->whereDate('cim.check_in_time', '>=', $fromDate)
                ->whereDate('cim.check_in_time', '<=', $toDate)
                ->where('member_id', '=', $memberId)
                ->paginate(10);
        } else {
            $results = DB::table('members')
                ->select(
                    'cim.id as cim_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cim.check_in_time',
                    'cim.check_out_time',
                    'branch_stores.name as branch_store_name'                    
                )
                ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
                ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
                ->join('branch_stores', 'members.branch_store_id', '=', 'branch_stores.id')                
                ->whereDate('cim.check_in_time', '>=', $fromDate)
                ->whereDate('cim.check_in_time', '<=', $toDate)
                // ->get();
                ->paginate(10);
        }

        if ($excel && $excel == "1") {
            return Excel::download(new MemberCheckInReportExport(), 'Member-checkin-report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'Report Member Check In',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'results'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'members'               => $member,
            'memberId'              => $memberId,
            'users'                 => User::get(),
            'content'               => 'admin/gym-report/report-member-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }


    public function reportMemberPTCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $memberId   = Request()->input('memberId');
        $excel      = Request()->input('excel');
        $branchId = Auth::user()->branch_store_id;
        $member = Member::all();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate   = NowDate();
        }

        if ($memberId) {
            $results = DB::table('members')
                ->select(
                    'cits.id as cits_id',
                    'members.member_code',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cits.pt_id as pt_id',
                    'pt.full_name as trainer_name',
                    'cits.check_in_time',
                    'cits.check_out_time',
                    'branch_stores.name as branch_store_name'                    
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
                ->join('branch_stores', 'ts.branch_store_id', '=', 'branch_stores.id')                  
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                ->where('member_id', '=', $memberId)
                ->where('ts.branch_store_id', $branchId)
                ->paginate(10);
        } else {
            $results = DB::table('members')
                ->select(
                    'cits.id as cits_id',
                    'members.member_code',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cits.pt_id as pt_id',
                    'pt.full_name as trainer_name',
                    'cits.check_in_time',
                    'cits.check_out_time',
                    'branch_stores.name as branch_store_name'                    
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
                ->join('branch_stores', 'ts.branch_store_id', '=', 'branch_stores.id')                  
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                ->where('ts.branch_store_id', $branchId)
                ->paginate(10);
        }

        if ($excel && $excel == "1") {
            return Excel::download(new MemberPTCheckInReportExport(), 'Member-PT-checkin-report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'Report Member PT Check In',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'results'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'members'               => $member,
            'memberId'              => $memberId,
            'users'                 => User::get(),
            'content'               => 'admin/gym-report/report-member-pt-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }    
}