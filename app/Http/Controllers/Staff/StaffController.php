<?php

namespace App\Http\Controllers\Staff;

use App\Exports\DetailSellingLeadGeneralReportExport;
use App\Exports\DetailSellingPTReportExport;
use App\Exports\LOReportExport;
use App\Exports\MemberCheckInReport;
use App\Exports\MemberCheckInReportExport;
use App\Exports\MemberPTCheckInReportExport;
use App\Exports\ReportMemberPTCheckInExport;
use App\Exports\StaffExport;
use App\Exports\TotalSellingLeadGeneralReportExport;
use App\Exports\TotalSellingPTReportExport;
use App\Http\Controllers\Controller;
use App\Models\BranchStore;
use App\Models\Member\Member;
use App\Models\Staff\ClassInstructor;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Psr7\Request;

class StaffController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new StaffExport(), 'staff, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'Staff List',
            'administrator'         => User::with("branchStore")->where('role', 'ADMIN')->get(),
            'classInstructor'       => ClassInstructor::get(),
            'customerService'       => User::with("branchStore")->where('role', 'CS')->get(),
            'customerServicePos'    => User::with("branchStore")->where('role', 'CSPOS')->get(),
            'fitnessConsultant'     => User::with("branchStore")->where('role', 'FC')->get(),
            'personalTrainer'       => PersonalTrainer::with("branchStore")->get(),
            'users'                 => User::get(),
            'branch_stores'         => BranchStore::get(),
            "page"                  => Request()->input('page'),
            'content'               => 'admin/staff/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function ptTotalReport()
    {
        $fromDate       = Request()->input('fromDate');
        $fromDate       = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate         = Request()->input('toDate');
        $toDate         = $toDate ? DateFormat($toDate) : NowDate();
        $personalTrainer = Request()->input('trainerName');
        $pdf            = Request()->input('pdf');

        $results = PersonalTrainer::select('personal_trainers.full_name as trainer_name', DB::raw('COUNT(personal_trainers.id) as pt_total'))
            ->join('check_in_trainer_sessions', 'check_in_trainer_sessions.pt_id', '=', 'personal_trainers.id')
            ->whereNotNull('check_in_trainer_sessions.check_out_time')
            ->whereDate('check_in_trainer_sessions.check_in_time', '>=', $fromDate) // Ini bukannya harus pakai start_date ?
            ->whereDate('check_in_trainer_sessions.check_in_time', '<=', $toDate)
            ->where('personal_trainers.id', '=', $personalTrainer)
            ->groupBy('personal_trainers.id', 'personal_trainers.full_name')
            ->orderBy('personal_trainers.full_name', 'asc')
            ->get();

        if ($pdf && $pdf == '1') {
            $pdf = Pdf::loadView('admin/gym-report/pt-total', [
                'result'   => $results,
            ]);
            return $pdf->stream('PT-Total-Report.pdf');
        }

        $data = [
            'title'                 => 'Personal Trainer Total Report',
            'personalTrainer'       => $personalTrainer,
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'classInstructor'       => ClassInstructor::get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'customerServicePos'    => User::where('role', 'CSPOS')->get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'users'                 => User::get(),
            'page'                  => Request()->input('page'),
            'content'               => 'admin/gym-report/pt-total'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function lo()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $fcId       = Request()->input('fcId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');
        $ptId       = Request()->input('ptId');

        $pt = PersonalTrainer::get();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        if ($ptId) {
            $results = Member::select('members.full_name as member_name', 'members.lo_start_date', 'members.lo_end', 'pt.full_name as pt_name')
                ->join('personal_trainers as pt', 'members.lo_pt_by', '=', 'pt.id')
                ->where('lo_is_used', 1)
                ->where('members.lo_pt_by', '=', $ptId)
                ->whereDate('lo_start_date', '>=', $fromDate)
                ->whereDate('lo_end', '<=', $toDate)
                ->get();
        } else {
            $results = Member::select('members.full_name as member_name', 'members.lo_start_date', 'members.lo_end', 'pt.full_name as pt_name')
                ->join('personal_trainers as pt', 'members.lo_pt_by', '=', 'pt.id')
                ->where('lo_is_used', 1)
                ->whereDate('lo_start_date', '>=', $fromDate)
                ->whereDate('lo_end', '<=', $toDate)
                ->get();
        }

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/lo', [
        //         'result'   => $results,
        //     ]);
        //     return $pdf->stream('LO.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new LOReportExport(), 'LO-Report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }



        $data = [
            'title'                 => 'LO Report',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'classInstructor'       => ClassInstructor::get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'customerServicePos'    => User::where('role', 'CSPOS')->get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'pt'                    => $pt,
            'ptId'                  => $ptId,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            // 'personalTrainer'       => $personalTrainer,
            'users'                 => User::get(),
            'page'                  => Request()->input('page'),
            'content'               => 'admin/gym-report/lo'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function ptDetailReport()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();
        $pdf = Request()->input('pdf');

        $personalTrainer = Request()->input('trainerName');

        $results = CheckInTrainerSession::select(
            'personal_trainers.full_name as trainer_name',
            'check_in_trainer_sessions.check_in_time',
            'check_in_trainer_sessions.check_out_time',
            'members.full_name as member_name',
            'trainer_packages.package_name'
        )
            ->join('personal_trainers', 'check_in_trainer_sessions.pt_id', '=', 'personal_trainers.id')
            ->join('trainer_sessions', 'check_in_trainer_sessions.trainer_session_id', '=', 'trainer_sessions.id')
            ->join('trainer_packages', 'trainer_sessions.trainer_package_id', '=', 'trainer_packages.id')
            ->join('members', 'trainer_sessions.member_id', '=', 'members.id')
            ->whereNotNull('check_in_trainer_sessions.check_out_time')
            ->whereDate('check_in_time', '>=', $fromDate)
            ->whereDate('check_in_time', '<=', $toDate)
            ->where('personal_trainers.id', '=', $personalTrainer)
            ->orderBy('personal_trainers.full_name')
            ->get();

        if ($pdf && $pdf == '1') {
            $pdf = Pdf::loadView('admin/gym-report/pt-detail', [
                'result'   => $results,
            ]);
            return $pdf->stream('PT-Detail-Report.pdf');
        }


        $data = [
            'title'                 => 'PT Detail Report',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'classInstructor'       => ClassInstructor::get(),
            'personalTrainer'       => $personalTrainer,
            'customerService'       => User::where('role', 'CS')->get(),
            'customerServicePos'    => User::where('role', 'CSPOS')->get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'users'                 => User::get(),
            'page'                  => Request()->input('page'),
            'content'               => 'admin/gym-report/pt-detail'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function reportMemberPTCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $memberId   = Request()->input('memberId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $member = Member::all();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate   = NowDate();
        }

        if ($memberId) {
            $results = DB::table('members')
                ->select(
                    'cits.id as cits_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    // 'cits.pt_id as pt_id',
                    // 'pt.full_name as trainer_name',
                    'cits.check_in_time',
                    'cits.check_out_time'
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                // ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                ->where('member_id', '=', $memberId)
                // ->get();
                ->paginate(10);
        } else {
            $results = DB::table('members')
                ->select(
                    'cits.id as cits_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    // 'cits.pt_id as pt_id',
                    // 'pt.full_name as trainer_name',
                    'cits.check_in_time',
                    'cits.check_out_time'
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                // ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                // ->get();
                ->paginate(10);
        }

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/report-member-pt-checkin', [
        //         'result'   => $results,
        //     ]);
        //     return $pdf->stream('Report-PT-checkin-' . $fromDate . '-' . $toDate . '.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new MemberPTCheckInReportExport(), 'Member-PT-checkin-report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'Report Member PT Check In',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'members'               => $member,
            'memberId'              => $memberId,
            'users'                 => User::get(),
            'content'               => 'admin/gym-report/report-member-pt-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function reportMemberCheckIn()
    {
        // $fromDate   = Request()->input('fromDate');
        // $fromDate  = $fromDate ?  DateFormat($fromDate) : NowDate();

        // $toDate     = Request()->input('toDate');
        // $toDate = $toDate ? DateFormat($toDate) : NowDate();
        // $pdf = Request()->input('pdf');

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
                    'cim.check_out_time'
                )
                ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
                ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
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
                    'cim.check_out_time'
                )
                ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
                ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
                ->whereDate('cim.check_in_time', '>=', $fromDate)
                ->whereDate('cim.check_in_time', '<=', $toDate)
                // ->get();
                ->paginate(4);
        }

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/report-member-checkin', [
        //         'result'   => $results,
        //     ]);
        //     return $pdf->stream('Report-member-checkin-' . $fromDate . '-' . $toDate . '.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new MemberCheckInReportExport(), 'Member-checkin-report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'Report Member Check In',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'members'               => $member,
            'memberId'              => $memberId,
            'users'                 => User::get(),
            'content'               => 'admin/gym-report/report-member-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function reportPTCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $memberId   = Request()->input('memberId');
        $pdf        = Request()->input('pdf');

        $member = Member::all();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate   = NowDate();
        }

        if ($memberId) {
            $results = DB::table('members')
                ->select(
                    'cim.id as cim_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cim.check_in_time',
                    'cim.check_out_time'
                )
                ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
                ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
                ->whereDate('cim.check_in_time', '>=', $fromDate)
                ->whereDate('cim.check_in_time', '<=', $toDate)
                ->where('member_id', '=', $memberId)
                ->get();
        } else {
            $results = DB::table('members')
                ->select(
                    'cim.id as cim_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cim.check_in_time',
                    'cim.check_out_time'
                )
                ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
                ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
                ->whereDate('cim.check_in_time', '>=', $fromDate)
                ->whereDate('cim.check_in_time', '<=', $toDate)
                ->get();
        }

        if ($pdf && $pdf == '1') {
            $pdf = Pdf::loadView('admin/gym-report/report-member-checkin', [
                'result'   => $results,
            ]);
            return $pdf->stream('Report-member-checkin, ' . $fromDate . '-' . $toDate . '.pdf');
        }


        $data = [
            'title'                 => 'Report PT Check In',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'users'                 => User::get(),
            'members'               => $member,
            'memberId'              => $memberId,
            'content'               => 'admin/gym-report/report-member-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function csDetailReportMemberCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();
        $pdf = Request()->input('pdf');

        $results = User::select('users.full_name as cs_name', 'members.full_name as member_name', 'member_packages.package_name')
            ->join('member_registrations as mr', 'users.id', '=', 'mr.user_id')
            ->join('member_packages', 'mr.member_package_id', '=', 'member_packages.id')
            ->join('members', 'members.id', '=', 'mr.member_id')
            ->whereDate('mr.created_at', '>=', $fromDate)
            ->whereDate('mr.created_at', '<=', $toDate)
            ->where('users.role', 'CS')
            ->orderBy('users.full_name')
            ->get();

        if ($pdf && $pdf == '1') {
            $pdf = Pdf::loadView('admin/gym-report/cs-detail-report-member-checkin', [
                'result'   => $results,
            ]);
            return $pdf->stream('CS-Detail-Member-CheckIn-Report.pdf');
        }


        $data = [
            'title'                 => 'CS Detail Input Member Check In',
            'customerService'       => User::where('role', 'CS')->get(),
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'content'               => 'admin/gym-report/cs-detail-report-member-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function csTotalReportPT()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();
        $pdf = Request()->input('pdf');

        $results = User::select('users.full_name as cs_name', DB::raw('COUNT(users.id) as cs_total'))
            ->join('trainer_sessions', 'trainer_sessions.user_id', '=', 'users.id')
            ->whereDate('trainer_sessions.created_at', '>=', $fromDate)
            ->whereDate('trainer_sessions.created_at', '<=', $toDate)
            ->where('users.role', '=', 'CS')
            ->groupBy('users.id', 'users.full_name')
            ->orderBy('users.full_name', 'asc')
            ->get();

        if ($pdf && $pdf == '1') {
            $pdf = Pdf::loadView('admin/gym-report/cs-total-report-pt', [
                'result'   => $results,
            ]);
            return $pdf->stream('CS-Total-Report-pt, ' . $fromDate . '-' . $toDate . '.pdf');
        }


        $data = [
            'title'                 => 'CS Total Input PT',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'CS')->get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'users'                 => User::get(),
            'content'               => 'admin/gym-report/cs-total-report-pt'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function csDetailReportPT()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();
        $pdf = Request()->input('pdf');

        $results = User::select('users.full_name as cs_name', 'members.full_name as member_name', 'trainer_packages.package_name')
            ->join('trainer_sessions as ts', 'users.id', '=', 'ts.user_id')
            ->join('trainer_packages', 'ts.trainer_package_id', '=', 'trainer_packages.id')
            ->join('members', 'members.id', '=', 'ts.member_id')
            ->whereDate('ts.created_at', '>=', $fromDate)
            ->whereDate('ts.created_at', '<=', $toDate)
            ->where('users.role', 'CS')
            ->orderBy('users.full_name')
            ->get();

        if ($pdf && $pdf == '1') {
            $pdf = Pdf::loadView('admin/gym-report/cs-detail-report-pt', [
                'result'   => $results,
            ]);
            return $pdf->stream('CS-Detail-pt-Report.pdf');
        }


        $data = [
            'title'                 => 'CS Detail Input PT',
            'customerService'       => User::where('role', 'CS')->get(),
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'content'               => 'admin/gym-report/cs-detail-report-pt'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function fcTotalReportMemberCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate   = $fromDate ? DateFormat($fromDate) : NowDate();
        $fcId       = Request()->input('fcId');

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();
        $pdf = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $fc         = User::where('role', 'fc')->get();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        if ($fcId) {
            $results = User::select('users.full_name as fc_name', 'member_registrations.fc_id', DB::raw('COUNT(users.id) as fc_total'))
                ->join('member_registrations', 'member_registrations.fc_id', '=', 'users.id')
                ->whereDate('member_registrations.created_at', '>=', $fromDate)
                ->whereDate('member_registrations.created_at', '<=', $toDate)
                // ->where('users.role', '=', 'FC')
                ->where('member_registrations.fc_id', '=', $fcId)
                ->groupBy('users.id', 'users.full_name', 'member_registrations.fc_id')
                ->orderBy('users.full_name', 'asc')
                ->get();
        } else {
            $results = User::select('users.full_name as fc_name', DB::raw('COUNT(users.id) as fc_total'))
                ->join('member_registrations', 'member_registrations.fc_id', '=', 'users.id')
                ->whereDate('member_registrations.created_at', '>=', $fromDate)
                ->whereDate('member_registrations.created_at', '<=', $toDate)
                ->where('users.role', '=', 'FC')
                ->groupBy('users.id', 'users.full_name')
                ->orderBy('users.full_name', 'asc')
                ->get();
        }

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/fc-total-report-member-checkin', [
        //         'result'   => $results,
        //     ]);
        //     return $pdf->stream('FC-Total-Report-Member-checkin, ' . $fromDate . '-' . $toDate . '.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new TotalSellingLeadGeneralReportExport(), 'Total-Selling-Lead-General-Report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'Total Selling Lead General Report',
            'administrator'         => User::where('role', 'ADMIN')->get(),
            'customerService'       => User::where('role', 'FC')->get(),
            'result'                => $results,
            'fromDate'              => $fromDate,
            'fc'                    => $fc,
            'fcId'                  => $fcId,
            'toDate'                => $toDate,
            'users'                 => User::get(),
            'content'               => 'admin/gym-report/fc-total-report-member-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function fcDetailReportMemberCheckIn()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $fcId       = Request()->input('fcId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $fc         = User::where('role', 'fc')->get();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        if ($fcId) {
            $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'member_packages.package_name', 'mr.package_price', 'mr.created_at')
                ->join('member_registrations as mr', 'users.id', '=', 'mr.fc_id')
                ->join('member_packages', 'mr.member_package_id', '=', 'member_packages.id')
                ->join('members', 'members.id', '=', 'mr.member_id')
                ->whereDate('mr.created_at', '>=', $fromDate)
                ->whereDate('mr.created_at', '<=', $toDate)
                ->where('mr.fc_id', '=', $fcId)
                // ->where('users.role', 'FC')
                ->orderBy('users.full_name')
                // ->get();
                ->paginate(10);
        } else {
            $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'member_packages.package_name', 'mr.package_price', 'mr.created_at')
                ->join('member_registrations as mr', 'users.id', '=', 'mr.fc_id')
                ->join('member_packages', 'mr.member_package_id', '=', 'member_packages.id')
                ->join('members', 'members.id', '=', 'mr.member_id')
                ->whereDate('mr.created_at', '>=', $fromDate)
                ->whereDate('mr.created_at', '<=', $toDate)
                // ->where('mr.fc_id', '=', $fcId)
                // ->where('users.role', 'FC')
                ->orderBy('users.full_name')
                // ->get();
                ->paginate(10);
        }

        if ($fcId) {
            $results->where('ts.member_id', '=', $fcId);
        }

        // $results = $results->get();

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/fc-detail-report-pt', [
        //         'result' => $results,
        //     ]);
        //     return $pdf->stream('Detail-Selling-Lead-General-Report' . $fromDate . '-' . $toDate . '.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new DetailSellingLeadGeneralReportExport(), 'Detail-Selling-Lead-General-Report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'             => 'Detail Selling Lead General Report',
            'personalTrainers'  => PersonalTrainer::get(),
            'result'            => $results,
            'fc'                => $fc,
            'fcId'              => $fcId,
            'fromDate'          => $fromDate,
            'toDate'            => $toDate,
            'content'           => 'admin/gym-report/fc-detail-report-member-checkin'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function fcTotalReportPT()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();
        $fcId       = Request()->input('fcId');

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();
        $pdf = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $fc         = User::where('role', 'fc')->get();

        if ($fcId) {
            $results = User::select('users.full_name as fc_name', DB::raw('COUNT(users.id) as fc_total'), 'trainer_sessions.package_price')
                ->join('trainer_sessions', 'trainer_sessions.fc_id', '=', 'users.id')
                ->whereDate('trainer_sessions.created_at', '>=', $fromDate)
                ->whereDate('trainer_sessions.created_at', '<=', $toDate)
                // ->where('users.role', '=', 'FC')
                ->where('trainer_sessions.fc_id', '=', $fcId)
                ->groupBy('users.id', 'users.full_name', 'trainer_sessions.package_price')
                ->orderBy('users.full_name', 'asc')
                ->get();
        } else {
            $results = User::select('users.full_name as fc_name', DB::raw('COUNT(users.id) as fc_total'), 'trainer_sessions.package_price')
                ->join('trainer_sessions', 'trainer_sessions.fc_id', '=', 'users.id')
                ->whereDate('trainer_sessions.created_at', '>=', $fromDate)
                ->whereDate('trainer_sessions.created_at', '<=', $toDate)
                ->where('users.role', '=', 'FC')
                ->groupBy('users.id', 'users.full_name', 'trainer_sessions.package_price')
                ->orderBy('users.full_name', 'asc')
                ->get();
        }

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/fc-total-report-pt', [
        //         'result'   => $results,
        //     ]);
        //     return $pdf->stream('FC-Total-Report-pt, ' . $fromDate . '-' . $toDate . '.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new TotalSellingPTReportExport(), 'Total-Selling-PT-Report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'FC Total Selling PT',
            'result'                => $results,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'fc'                    => $fc,
            'fcId'                  => $fcId,
            'content'               => 'admin/gym-report/fc-total-report-pt'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function fcDetailReportPT()
    {
        // $fromDate   = Request()->input('fromDate');
        // $fromDate  = $fromDate ?  DateFormat($fromDate) : NowDate();

        // $toDate     = Request()->input('toDate');
        // $toDate = $toDate ? DateFormat($toDate) : NowDate();
        // $pdf = Request()->input('pdf');

        // $fc = User::where('role', 'FC')->get();
        // // dd($fc);
        // $fcId = Request()->input('fcId');
        // $fromFc = $fcId ? $fcId : $fc;

        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $fcId       = Request()->input('fcId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $fc = User::where('role', 'fc')->get();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        if ($fcId) {
            $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'trainer_packages.package_name', 'ts.created_at', 'ts.package_price')
                ->join('trainer_sessions as ts', 'users.id', '=', 'ts.fc_id')
                ->join('trainer_packages', 'ts.trainer_package_id', '=', 'trainer_packages.id')
                ->join('members', 'members.id', '=', 'ts.member_id')
                ->whereDate('ts.created_at', '>=', $fromDate)
                ->whereDate('ts.created_at', '<=', $toDate)
                ->where('ts.fc_id', '=', $fcId)
                ->where('users.role', 'FC')
                ->orderBy('users.full_name')
                // ->get();
                ->paginate(5);
        } else {
            $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'trainer_packages.package_name', 'ts.created_at', 'ts.package_price')
                ->join('trainer_sessions as ts', 'users.id', '=', 'ts.fc_id')
                ->join('trainer_packages', 'ts.trainer_package_id', '=', 'trainer_packages.id')
                ->join('members', 'members.id', '=', 'ts.member_id')
                ->whereDate('ts.created_at', '>=', $fromDate)
                ->whereDate('ts.created_at', '<=', $toDate)
                // ->where('ts.fc_id', '=', $fcId)
                ->where('users.role', 'FC')
                ->orderBy('users.full_name')
                // ->get();
                ->paginate(5);
        }


        if ($fcId) {
            $results->where('trainer_sessions.fc_id', '=', $fcId);
        }

        // if ($pdf && $pdf == '1') {
        //     $pdf = Pdf::loadView('admin/gym-report/fc-detail-report-pt', [
        //         'result'   => $results,
        //     ]);
        //     return $pdf->stream('FC-Detail-PT-Selling-Report-' . $fromDate . '-' . $toDate . '.pdf');
        // }

        if ($excel && $excel == "1") {
            return Excel::download(new DetailSellingPTReportExport(), 'Detail-Selling-PT-Report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'FC Detail PT Selling Report',
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'fc'                    => $fc,
            'fcId'                  => $fcId,
            'fromDate'              => $fromDate,
            // 'fromFc'                => $fromFc,
            'toDate'                => $toDate,
            'content'               => 'admin/gym-report/fc-detail-report-pt'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function oneVisit()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $fcId       = Request()->input('fcId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $fc = User::where('role', 'fc')->get();

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        // if ($fcId) {
        $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'trainer_packages.package_name', 'ts.created_at', 'ts.package_price')
            ->join('trainer_sessions as ts', 'users.id', '=', 'ts.fc_id')
            ->join('trainer_packages', 'ts.trainer_package_id', '=', 'trainer_packages.id')
            ->join('members', 'members.id', '=', 'ts.member_id')
            ->whereDate('ts.created_at', '>=', $fromDate)
            ->whereDate('ts.created_at', '<=', $toDate)
            ->where('ts.fc_id', '=', $fcId)
            ->where('users.role', 'FC')
            ->orderBy('users.full_name')
            // ->get();
            ->paginate(5);
        // } else {
        //     $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'trainer_packages.package_name', 'ts.created_at', 'ts.package_price')
        //         ->join('trainer_sessions as ts', 'users.id', '=', 'ts.fc_id')
        //         ->join('trainer_packages', 'ts.trainer_package_id', '=', 'trainer_packages.id')
        //         ->join('members', 'members.id', '=', 'ts.member_id')
        //         ->whereDate('ts.created_at', '>=', $fromDate)
        //         ->whereDate('ts.created_at', '<=', $toDate)
        //         // ->where('ts.fc_id', '=', $fcId)
        //         ->where('users.role', 'FC')
        //         ->orderBy('users.full_name')
        //         // ->get();
        //         ->paginate(5);
        // }


        if ($fcId) {
            $results->where('trainer_sessions.fc_id', '=', $fcId);
        }

        if ($excel && $excel == "1") {
            return Excel::download(new DetailSellingPTReportExport(), 'Detail-Selling-PT-Report, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $data = [
            'title'                 => 'FC Detail PT Selling Report',
            'personalTrainers'      => PersonalTrainer::get(),
            'result'                => $results,
            'fc'                    => $fc,
            'fcId'                  => $fcId,
            'fromDate'              => $fromDate,
            // 'fromFc'                => $fromFc,
            'toDate'                => $toDate,
            'content'               => 'admin/gym-report/one-visit'
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
