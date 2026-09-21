<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchStore;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\PtLeaveDay;
use App\Models\Trainer\TrainerSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = Auth::user()->branch_store_id;
        $activeBranchStore = BranchStore::find($branchId);
        $canViewDashboardFinance = optional($activeBranchStore)
            ->canRoleViewDashboardFinance(Auth::user()->role);
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $installmentReminderEnabled = (bool) optional($activeBranchStore)->member_installment_enabled;
        $installmentReminderDays = max(0, (int) optional($activeBranchStore)->member_installment_reminder_days);
        $installmentReminders = collect();
        $overdueInstallmentReminderCount = 0;

        if ($installmentReminderEnabled) {
            $today = Carbon::today();
            $reminderUntil = $today->copy()->addDays($installmentReminderDays);

            $installmentReminders = DB::table('member_registration_installments as i')
                ->join('member_registrations as mr', 'mr.id', '=', 'i.member_registration_id')
                ->where('mr.days', '>', 1)
                ->join('members as m', 'm.id', '=', 'mr.member_id')
                ->join('member_packages as mp', 'mp.id', '=', 'mr.member_package_id')
                ->select(
                    'mr.id as member_registration_id',
                    'm.full_name as member_name',
                    'm.member_code',
                    'mp.package_name'
                )
                ->selectRaw('MIN(i.due_date) as earliest_due_date')
                ->selectRaw('COUNT(i.id) as unpaid_installment_count')
                ->selectRaw('SUM(GREATEST(i.amount - i.paid_amount, 0)) as outstanding_amount')
                ->selectRaw('GROUP_CONCAT(i.month_number ORDER BY i.due_date SEPARATOR ", ") as unpaid_months')
                ->selectRaw('SUM(CASE WHEN i.due_date < ? THEN 1 ELSE 0 END) as overdue_installment_count', [
                    $today->toDateString(),
                ])
                ->where('m.branch_store_id', $branchId)
                ->where('mr.is_installment_plan', true)
                ->where('i.type', 'monthly')
                ->whereIn('i.status', ['pending', 'partial', 'overdue'])
                ->whereColumn('i.paid_amount', '<', 'i.amount')
                ->whereDate('i.due_date', '<=', $reminderUntil->toDateString())
                ->where(function ($query) {
                    $query->whereNull('mr.installment_status')
                        ->orWhere('mr.installment_status', '<>', 'cancelled');
                })
                ->groupBy(
                    'mr.id',
                    'm.full_name',
                    'm.member_code',
                    'mp.package_name'
                )
                ->orderByDesc('overdue_installment_count')
                ->orderBy('earliest_due_date')
                ->get();

            $overdueInstallmentReminderCount = $installmentReminders
                ->where('overdue_installment_count', '>', 0)
                ->count();
        }
        
        $incomeOfMember = collect();
        $incomeOfPT = collect();

        if ($canViewDashboardFinance) {
            // Income of Member Registrations
            $incomeOfMember = DB::table('member_registrations as a')
                ->where('a.days', '>', 1)
                ->select(
                    'a.package_price',
                    'a.start_date',
                    'a.admin_price',
                    'a.id',
                    DB::raw('SUM(a.package_price) as total_price'),
                    DB::raw('SUM(a.admin_price) as admin_price')
                )
                ->join('members', 'member_id', '=', 'members.id')
                ->where('a.days', '>', '1')
                ->where('members.branch_store_id', $branchId)
                ->whereBetween('a.created_at', [$startDate, $endDate])
                ->groupBy(
                    'a.id',
                    'a.start_date',
                    'a.admin_price',
                    'a.description',
                    'a.package_price',
                )
                ->get();

            $incomeOfPT = DB::table('trainer_sessions as a')
            ->whereNotIn('a.trainer_package_id', function ($query) {
                $query->select('id')->from('trainer_packages')->where('status', 'LGT');
            })
                ->select(
                    'a.package_price',
                    'a.start_date',
                    'a.admin_price',
                    'a.id',
                    DB::raw('DATE_ADD(a.start_date, INTERVAL (a.days + COALESCE(pt_freeze_summary.total_days, 0)) DAY) as expired_date'),
                    DB::raw('SUM(a.package_price) as total_price'),
                    DB::raw('SUM(a.admin_price) as admin_price')
                )
                ->join('trainer_packages as b', 'a.trainer_package_id', '=', 'b.id')
                ->where(function ($query) {
                    $query->whereNull('b.status')->orWhere('b.status', '!=', 'LGT');
                })
                ->leftJoinSub(PtLeaveDay::summaryQuery(), 'pt_freeze_summary', function ($join) {
                    $join->on('a.id', '=', 'pt_freeze_summary.trainer_session_id');
                })
                ->where('a.branch_store_id', $branchId)
                ->where('a.is_pt_free', false)
                ->whereNull('b.status')
                ->whereBetween('a.created_at', [$startDate, $endDate])
                ->groupBy(
                    'a.id',
                    'a.start_date',
                    'a.admin_price',
                    'a.description',
                    'a.package_price',
                    'expired_date',
                    'status'
                )
                ->get();

        }

        $totalMembers = DB::table('members as a')
            ->select(
                'a.id',
                'a.full_name',
                'a.nickname',
                'a.member_code',
                'a.gender',
                'a.born',
                'a.status',
            )
            ->where('a.status', '=', 'sell')
            ->where('a.branch_store_id', $branchId)                
            ->count();


        // MEMBER REGISTRATION
        $memberRegisterActive = DB::table('member_registrations as a')
            ->where('a.days', '>', 1)
            ->join('members', 'member_id', '=', 'members.id')       
            ->where('members.branch_store_id', $branchId)                  
            ->where('a.days', '>', '1')           
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->count();

        $memberRegisterExpired = DB::table('member_registrations as a')
            ->where('a.days', '>', 1)
            ->join('members', 'member_id', '=', 'members.id')       
            ->where('members.branch_store_id', $branchId)              
            ->where('a.days', '>', '1')
            ->whereRaw('NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->count();

        $memberRegisterPending = DB::table('member_registrations as a')
            ->where('a.days', '>', 1)
            ->join('members', 'member_id', '=', 'members.id')       
            ->where('members.branch_store_id', $branchId)              
            ->where('a.days', '>', '1')
            ->whereRaw('NOW() < a.start_date')
            ->count();

        // TRAINER SESSION
        $totalTrainerSessions = DB::table('trainer_sessions as a')
            ->whereNotIn('a.trainer_package_id', function ($query) {
                $query->select('id')->from('trainer_packages')->where('status', 'LGT');
            })
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->where(function ($query) {
                $query->whereNull('c.status')->orWhere('c.status', '!=', 'LGT');
            })
            ->join('personal_trainers as d', 'a.trainer_id', '=', 'd.id')
            ->join('users as e', 'a.user_id', '=', 'e.id')
            ->where('a.branch_store_id', $branchId)              
            ->where('a.is_pt_free', false)
            ->whereNull('c.status')
            ->count();

        $trainerSessionActive = DB::table('trainer_sessions as a')
            ->whereNotIn('a.trainer_package_id', function ($query) {
                $query->select('id')->from('trainer_packages')->where('status', 'LGT');
            })
            ->addSelect(
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL (a.days + COALESCE(pt_freeze_summary.total_days, 0)) DAY) THEN "Over" ELSE "Running" END as expired_date_status'),
                DB::raw('IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) as remaining_sessions'),
                DB::raw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status'),
            )
            ->join('trainer_packages as c', function ($join) {
                $join->on('a.trainer_package_id', '=', 'c.id')
                    ->whereNull('c.status');
            })
            ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions where check_out_time is not null
                                    GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
            ->leftJoin(DB::raw("(select a.* from check_in_trainer_sessions a inner join (SELECT max(id) as id FROM check_in_trainer_sessions
                                    group by trainer_session_id) as b on a.id=b.id) as cits"), 'cits.trainer_session_id', '=', 'a.id')
            ->leftJoinSub(PtLeaveDay::summaryQuery(), 'pt_freeze_summary', function ($join) {
                $join->on('a.id', '=', 'pt_freeze_summary.trainer_session_id');
            })
            ->where('a.branch_store_id', $branchId)                                      
            ->where('a.is_pt_free', false)
            ->whereRaw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL (a.days + COALESCE(pt_freeze_summary.total_days, 0)) DAY)')
            ->count();

        $trainerSessionExpired = DB::table('trainer_sessions as a')
            ->whereNotIn('a.trainer_package_id', function ($query) {
                $query->select('id')->from('trainer_packages')->where('status', 'LGT');
            })
            ->addSelect(
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL (a.days + COALESCE(pt_freeze_summary.total_days, 0)) DAY) THEN "Over" ELSE "Running" END as expired_date_status'),
                DB::raw('IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) as remaining_sessions'),
                DB::raw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status'),
            )
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->where(function ($query) {
                $query->whereNull('c.status')->orWhere('c.status', '!=', 'LGT');
            })
            ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions where check_out_time is not null
                                    GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
            ->leftJoin(DB::raw("(select a.* from check_in_trainer_sessions a inner join (SELECT max(id) as id FROM check_in_trainer_sessions
                                    group by trainer_session_id) as b on a.id=b.id) as cits"), 'cits.trainer_session_id', '=', 'a.id')
            ->leftJoinSub(PtLeaveDay::summaryQuery(), 'pt_freeze_summary', function ($join) {
                $join->on('a.id', '=', 'pt_freeze_summary.trainer_session_id');
            })
            ->where('a.branch_store_id', $branchId)                                      
            ->where('a.is_pt_free', false)
            ->whereRaw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
            ->whereRaw('NOW() > DATE_ADD(a.start_date, INTERVAL (a.days + COALESCE(pt_freeze_summary.total_days, 0)) DAY)')
            ->whereNull('c.status')
            ->count();

        $data = [
            'title'                             => 'Dashboard Admin',
            'incomeOfActiveMember'              => $incomeOfMember,
            'incomeOfActivePT'                  => $incomeOfPT,
            'canViewDashboardFinance'           => $canViewDashboardFinance,
            'installmentReminderEnabled'        => $installmentReminderEnabled,
            'installmentReminderDays'           => $installmentReminderDays,
            'installmentReminders'              => $installmentReminders,
            'overdueInstallmentReminderCount'   => $overdueInstallmentReminderCount,
            'branch_stores'                     => BranchStore::get(),
            'totalMember'                       => $totalMembers,
            'totalMemberRegister'               => MemberRegistration::where('days', '>', 1)->count(),
            'memberRegisterActive'              => $memberRegisterActive,
            'memberRegisterPending'             => $memberRegisterPending,
            'memberRegisterExpired'             => $memberRegisterExpired,

            'totalTrainerSessions'              => $totalTrainerSessions,
            'trainerSessionActive'              => $trainerSessionActive,
            'trainerSessionExpired'             => $trainerSessionExpired,


            'totalMembers'                      => $totalMembers,

            'members'                           => Member::take(5)->get(),
            'trainers'                          => PersonalTrainer::take(5)->get(),
            'totalPersonalTrainers'             => PersonalTrainer::count(),
            'content'                           => 'admin/dashboard/index'
        ];
        return view('admin.layouts.wrapper-dashboard', $data);
    }
}
