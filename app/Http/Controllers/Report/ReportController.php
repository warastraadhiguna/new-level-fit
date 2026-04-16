<?php

namespace App\Http\Controllers\Report;

use App\Exports\MemberCheckInReportExport;
use App\Exports\MemberPTCheckInReportExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Staff\PersonalTrainer;
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

        $results = DB::table('members')
            ->select(
                'cim.id as cim_id',
                'members.id as member_id',
                'members.full_name as member_name',
                'cim.check_in_time',
                'cim.check_out_time',
                DB::raw('COALESCE(check_in_branch.name, member_branch.name) as branch_store_name')
            )
            ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
            ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
            ->leftJoin('branch_stores as check_in_branch', 'cim.branch_store_id', '=', 'check_in_branch.id')
            ->leftJoin('branch_stores as member_branch', 'members.branch_store_id', '=', 'member_branch.id')
            ->whereDate('cim.check_in_time', '>=', $fromDate)
            ->whereDate('cim.check_in_time', '<=', $toDate)
            ->when($memberId, function ($q) use ($memberId) {
                $q->where('members.id', $memberId);
            })
            ->orderBy('cim.check_in_time', 'desc') 
            ->paginate(10);

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
        $ptId   = Request()->input('ptId');
        $excel      = Request()->input('excel');
        $branchId = Auth::user()->branch_store_id;
        $member = Member::all();
        $trainers = PersonalTrainer::all();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate   = NowDate();
        }

        $results = DB::table('members')
            ->select(
                'cits.id as cits_id',
                'members.member_code',
                'members.id as member_id',
                'members.full_name as member_name',
                'cits.pt_id as pt_id',
                'pt.full_name as trainer_name',
                'tp.package_name',
                'cits.check_in_time',
                'cits.check_out_time',
                DB::raw('COALESCE(check_in_branch.name, session_branch.name) as branch_store_name')
            )
            ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
            ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
            ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
            ->leftJoin('branch_stores as check_in_branch', 'cits.branch_store_id', '=', 'check_in_branch.id')
            ->leftJoin('branch_stores as session_branch', 'ts.branch_store_id', '=', 'session_branch.id')
            ->join('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->whereBetween(DB::raw('DATE(cits.check_in_time)'), [$fromDate, $toDate])
            ->whereRaw('COALESCE(cits.branch_store_id, ts.branch_store_id) = ?', [$branchId])
            ->when($memberId, fn ($q) => $q->where('members.id', $memberId))            
            ->when($ptId, fn ($q) => $q->where('pt.id', $ptId))
            ->orderBy('cits.check_in_time', 'desc')             
            ->paginate(10);

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
            'ptId'                  => $ptId,            
            'users'                 => User::get(),
            'trainers'              => $trainers,
            'content'               => 'admin/gym-report/report-member-pt-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }    
}
