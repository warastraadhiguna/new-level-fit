<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\TrainerSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Income of Member Registrations
        $incomeOfMember = DB::table('member_registrations as a')
            ->select(
                'a.package_price',
                'a.start_date',
                'a.admin_price',
                'a.id',
                DB::raw('SUM(a.package_price) as total_price'),
                DB::raw('SUM(a.admin_price) as admin_price')
            )
            ->where('a.days', '>', '1')
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
            ->select(
                'a.package_price',
                'a.start_date',
                'a.admin_price',
                'a.id',
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('SUM(a.package_price) as total_price'),
                DB::raw('SUM(a.admin_price) as admin_price')
            )
            ->join('trainer_packages as b', 'a.trainer_package_id', '=', 'b.id')
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

        $incomeOfActiveLGT = DB::table('trainer_sessions as a')
            ->select(
                'a.package_price',
                'a.start_date',
                'a.admin_price',
                'a.id',
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('SUM(a.package_price) as total_price'),
                DB::raw('SUM(a.admin_price) as admin_price')
            )
            ->join('trainer_packages as b', 'a.trainer_package_id', '=', 'b.id')
            ->where('b.status', 'LGT')
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

        $incomeOfOneDayVisit = DB::table('member_registrations as a')
            ->select(
                'a.package_price',
                'a.start_date',
                'a.admin_price',
                'a.id',
                DB::raw('SUM(a.package_price) as total_price'),
                DB::raw('SUM(a.admin_price) as admin_price')
            )
            ->join('member_packages as b', 'a.member_package_id', '=', 'b.id')
            ->where('a.days', '=', '1')
            ->whereBetween('a.created_at', [$startDate, $endDate])
            ->groupBy(
                'a.id',
                'a.start_date',
                'a.admin_price',
                'a.description',
                'a.package_price',
                // 'expired_date',
                // 'status'
            )
            ->get();

        // TOTAL MEMBERS
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
            ->count();


        // MEMBER REGISTRATION
        $memberRegisterActive = DB::table('member_registrations as a')
            ->where('a.days', '>', '1')
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->count();

        $memberRegisterExpired = DB::table('member_registrations as a')
            ->where('a.days', '>', '1')
            ->whereRaw('NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->count();

        $memberRegisterPending = DB::table('member_registrations as a')
            ->where('a.days', '>', '1')
            ->whereRaw('NOW() < a.start_date')
            ->count();

        // TRAINER SESSION
        $totalTrainerSessions = DB::table('trainer_sessions as a')
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->join('personal_trainers as d', 'a.trainer_id', '=', 'd.id')
            ->join('users as e', 'a.user_id', '=', 'e.id')
            ->whereNull('c.status')
            ->count();

        $trainerSessionActive = DB::table('trainer_sessions as a')
            ->addSelect(
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as expired_date_status'),
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
            ->whereRaw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->count();

        $trainerSessionExpired = DB::table('trainer_sessions as a')
            ->addSelect(
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as expired_date_status'),
                DB::raw('IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) as remaining_sessions'),
                DB::raw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status'),
            )
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions where check_out_time is not null
                                    GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
            ->leftJoin(DB::raw("(select a.* from check_in_trainer_sessions a inner join (SELECT max(id) as id FROM check_in_trainer_sessions
                                    group by trainer_session_id) as b on a.id=b.id) as cits"), 'cits.trainer_session_id', '=', 'a.id')
            ->whereRaw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
            ->whereRaw('NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->whereNull('c.status')
            ->count();

        // LEVEL GROUP TRAINING
        $totalLevelGroupTrainings = DB::table('trainer_sessions as a')
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            // ->join('trainer_packages as c', function ($join) {
            //     $join->on('a.trainer_package_id', '=', 'c.id')
            //         ->where('c.status', 'LGT');
            // })
            ->where('c.status', '=', 'LGT')
            ->count();

        $totalOneDayVisit = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.days as member_registration_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.id as member_id',
                'c.package_price',
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" 
                    WHEN NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Running" 
                    ELSE "Not Started" 
                END as status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join(
                'users as f',
                'a.user_id',
                '=',
                'f.id'
            )
            // ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->where('b.status', 'one_day_visit')
            ->count();

        $totalLGTActive = DB::table('trainer_sessions as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.days as member_registration_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.id as member_id',
                'c.package_price',
                'c.status'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" 
                    WHEN NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Running" 
                    ELSE "Not Started" 
                END as status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->join(
                'users as f',
                'a.user_id',
                '=',
                'f.id'
            )
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->where('c.status', 'LGT')
            ->count();

        $totalLGTExpired = DB::table('trainer_sessions as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.days as member_registration_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.id as member_id',
                'c.package_price',
                'c.status'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" 
                    WHEN NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Running" 
                    ELSE "Not Started" 
                END as status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->join(
                'users as f',
                'a.user_id',
                '=',
                'f.id'
            )
            // ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->whereRaw('NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->where('c.status', 'LGT')
            ->count();

        $data = [
            'title'                             => 'Dashboard Admin Level FIT',
            'incomeOfActiveMember'              => $incomeOfMember,
            'incomeOfActivePT'                  => $incomeOfPT,
            'incomeOfActiveLGT'                 => $incomeOfActiveLGT,
            'incomeOfOneDayVisit'               => $incomeOfOneDayVisit,

            'totalMember'                       => $totalMembers,
            'totalMemberRegister'               => MemberRegistration::where('days', '>', 1)->count(),
            'memberRegisterActive'              => $memberRegisterActive,
            'memberRegisterPending'             => $memberRegisterPending,
            'memberRegisterExpired'             => $memberRegisterExpired,

            'totalTrainerSessions'              => $totalTrainerSessions,
            'trainerSessionActive'              => $trainerSessionActive,
            'trainerSessionExpired'             => $trainerSessionExpired,

            'totalLevelGroupTrainings'          => $totalLevelGroupTrainings,

            'totalMembers'                      => $totalMembers,
            'totalOneDayVisit'                  => $totalOneDayVisit,

            'members'                           => Member::take(5)->get(),
            'trainers'                          => PersonalTrainer::take(5)->get(),
            'totalPersonalTrainers'             => PersonalTrainer::count(),
            'content'                           => 'admin/dashboard/index'
        ];
        return view('admin.layouts.wrapper-dashboard', $data);
    }
}
