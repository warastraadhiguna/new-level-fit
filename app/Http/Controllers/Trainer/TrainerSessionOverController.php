<?php

namespace App\Http\Controllers\Trainer;

use App\Exports\TrainerSessionExpiredExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Trainer\TrainerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class TrainerSessionOverController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new TrainerSessionExpiredExport(), 'trainer-session-expired, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $trainerSessions = TrainerSession::getExpiredPT();
        $data = [
            'title'             => 'PT Expired List',
            'trainerSessions'   => $trainerSessions,
            'content'           => 'admin/trainer-session-over/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function pdfReport()
    {
        $trainerSessionsOver = DB::table('trainer_sessions as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.package_price',
                'a.admin_price',
                'a.days',
                'b.full_name as member_name',
                'b.member_code',
                'c.package_name',
                'c.number_of_session',
                'd.full_name as trainer_name',
                'e.full_name as staff_name',
                DB::raw('MIN(a.created_at) as earliest_created_at'), // Added this line
                DB::raw('MAX(a.created_at) as latest_created_at')    // Added this line
            )
            ->addSelect(DB::raw('SUM(a.package_price) as total_price'))
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as expired_date_status')
            )
            ->addSelect(DB::raw('SUM(a.admin_price) as admin_price'))
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->join('personal_trainers as d', 'a.trainer_id', '=', 'd.id')
            ->join('users as e', 'a.user_id', '=', 'e.id')
            ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
            ->groupBy('a.id', 'a.start_date', 'a.description', 'a.package_price', 'a.admin_price', 'a.days', 'b.full_name', 'b.member_code', 'c.package_name', 'c.number_of_session', 'd.full_name', 'e.full_name', 'e.check_in_count')
            ->addSelect(DB::raw('IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) as remaining_sessions'))
            ->addSelect(DB::raw('CASE WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) > 0 THEN "Running" WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status'))
            // ->whereRaw('')
            ->having('session_status', '=', 'Over') // Use HAVING instead of WHERE
            ->get();

        $pdf = Pdf::loadView('admin/trainer-session-over/pdf', [
            'trainerSessionsOver'   => $trainerSessionsOver,
        ])->setPaper('a4', 'landscape');
        return $pdf->stream('trainer-sessions-over-report.pdf');
    }
}
