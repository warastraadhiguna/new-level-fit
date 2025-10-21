<?php

namespace App\Exports;

use App\Models\Member\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class MemberPTCheckInReportExport implements FromView
{
    public function view(): View
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
                    'cits.id as cits_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cits.pt_id as pt_id',
                    'pt.full_name as trainer_name',
                    'cits.check_in_time',
                    'cits.check_out_time'
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                ->where('member_id', '=', $memberId)
                ->get();
        } else {
            $results = DB::table('members')
                ->select(
                    'cits.id as cits_id',
                    'members.id as member_id',
                    'members.full_name as member_name',
                    'cits.pt_id as pt_id',
                    'pt.full_name as trainer_name',
                    'cits.check_in_time',
                    'cits.check_out_time'
                )
                ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
                ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
                ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
                ->whereDate('cits.check_in_time', '>=', $fromDate)
                ->whereDate('cits.check_in_time', '<=', $toDate)
                ->get();
        }

        return view('admin.gym-report.excel.report-member-pt-checkin', [
            'result' => $results
        ]);
    }

    public function styles($row): array
    {
        if ($row % 2 == 0) {
            return [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFC0C0C0'],
                ]
            ];
        } else {
            return [];
        }
    }
}
