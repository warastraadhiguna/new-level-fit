<?php

namespace App\Exports;

use App\Models\Member\MemberRegistration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class ReportMemberPTCheckInExport implements FromView
{
    public function view(): View
    {
        $nowTime = Carbon::now()->tz('Asia/Jakarta');
        dd("Tes");
        $nowTimeString = DateFormat($nowTime, "Y-MM-DD");
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $toDate = $toDate ? $toDate : $nowTimeString;
        $fromDate = $fromDate ? $fromDate : $nowTimeString;

        $results = DB::table('members')
                ->select(
                    'cits.id as cits_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cits.check_in_time',
                    'cits.check_out_time'
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                ->get();

        return view('admin.gym-report.excel.report-member-pt-checkin', [
            'trainerSessions' => $results
        ]);
    }
}
