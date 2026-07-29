<?php

namespace App\Exports;

use App\Models\Member\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromView;

class MemberPTCheckInReportExport implements FromView
{
    public function view(): View
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $memberId   = Request()->input('memberId');
        $ptId       = Request()->input('ptId');        
        $branchId   = Auth::user()->branch_store_id;

        if (!$fromDate || !$toDate) {
            $fromDate = NowDate();
            $toDate = NowDate();
        }

        $hasTrainerCheckInBranchStoreColumn = Schema::hasColumn('check_in_trainer_sessions', 'branch_store_id');
        $branchStoreNameSql = $hasTrainerCheckInBranchStoreColumn
            ? 'COALESCE(check_in_branch.name, session_branch.name) as branch_store_name'
            : 'session_branch.name as branch_store_name';

        $results = DB::table('members')
            ->select(
                'cits.id as cits_id',
                'members.member_code',
                'members.id as member_id',
                'members.full_name as member_name',
                DB::raw('COALESCE(cits.pt_id, ts.trainer_id) as pt_id'),
                DB::raw("COALESCE(check_in_pt.full_name, session_pt.full_name, '-') as trainer_name"),
                'tp.package_name',                
                'cits.check_in_time',
                'cits.check_out_time',
                DB::raw($branchStoreNameSql)
            )
            ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
            ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
            ->leftJoin('personal_trainers as check_in_pt', 'cits.pt_id', '=', 'check_in_pt.id')
            ->leftJoin('personal_trainers as session_pt', 'ts.trainer_id', '=', 'session_pt.id')
            ->leftJoin('branch_stores as session_branch', 'ts.branch_store_id', '=', 'session_branch.id')
            ->join('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')            
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereBetween(DB::raw('DATE(cits.check_in_time)'), [$fromDate, $toDate])
                    ->orWhereBetween(DB::raw('DATE(cits.check_out_time)'), [$fromDate, $toDate]);
            })
            ->when($hasTrainerCheckInBranchStoreColumn, function ($query) use ($branchId) {
                $query->leftJoin('branch_stores as check_in_branch', 'cits.branch_store_id', '=', 'check_in_branch.id')
                    ->whereRaw('COALESCE(cits.branch_store_id, ts.branch_store_id) = ?', [$branchId]);
            }, function ($query) use ($branchId) {
                $query->where('ts.branch_store_id', $branchId);
            })
            ->when($memberId, function ($query) use ($memberId) {
                $query->where('members.id', $memberId);
            })
            ->when($ptId, fn ($q) => $q->whereRaw('COALESCE(cits.pt_id, ts.trainer_id) = ?', [$ptId]))
            ->orderBy('cits.check_in_time', 'desc')                
            ->get();

        return view('admin.gym-report.excel.report-member-pt-checkin', [
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
