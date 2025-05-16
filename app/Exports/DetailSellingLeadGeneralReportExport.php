<?php

namespace App\Exports;

use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class DetailSellingLeadGeneralReportExport implements FromView
{
    public function view(): View
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $memberId   = Request()->input('memberId');
        $fcId       = Request()->input('fcId');
        $pdf        = Request()->input('pdf');
        $excel      = Request()->input('excel');

        $member = Member::all();

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
                ->get();
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
                ->get();
        }

        return view('admin.gym-report.excel.fc-detail-member-selling-report', [
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
