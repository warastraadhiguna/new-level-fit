<?php

namespace App\Exports;

use App\Models\Member\Member;
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
            ->when($memberId, function ($query) use ($memberId) {
                $query->where('members.id', $memberId);
            })
            ->orderBy('cim.check_in_time', 'desc') 
            ->get();
        // ->paginate(10);


        return view('admin.gym-report.excel.report-member-checkin', [
            'results' => $results
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