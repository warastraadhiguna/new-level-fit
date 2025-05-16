<?php

namespace App\Exports;

use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class MemberCheckInReportExport implements FromView
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
                // ->paginate(10);
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
                // ->paginate(10);
        }

        return view('admin.gym-report.excel.report-member-checkin', [
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
