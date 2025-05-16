<?php

namespace App\Exports;

use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class DetailSellingPTReportExport implements FromView
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
            $results = User::select('users.full_name as fc_name', 'members.full_name as member_name', 'trainer_packages.package_name', 'ts.created_at', 'ts.package_price')
                ->join('trainer_sessions as ts', 'users.id', '=', 'ts.fc_id')
                ->join('trainer_packages', 'ts.trainer_package_id', '=', 'trainer_packages.id')
                ->join('members', 'members.id', '=', 'ts.member_id')
                ->whereDate('ts.created_at', '>=', $fromDate)
                ->whereDate('ts.created_at', '<=', $toDate)
                ->where('ts.fc_id', '=', $fcId)
                ->where('users.role', 'FC')
                ->orderBy('users.full_name')
                ->get();
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
                ->get();
        }

        return view('admin.gym-report.excel.fc-detail-pt-selling-report', [
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
