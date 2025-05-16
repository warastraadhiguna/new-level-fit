<?php

namespace App\Exports;

use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\Staff\PersonalTrainer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class LOReportExport implements FromView
{
    public function view(): View
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

        return view('admin.gym-report.excel.lo-report', [
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
