<?php

namespace App\Exports;

use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class TrainerSessionExpiredExport implements FromView
{
    public function view(): View
    {
        $nowTime = Carbon::now()->tz('Asia/Jakarta');
        $nowTimeString = DateFormat($nowTime, "Y-MM-DD");
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $toDate = $toDate ? $toDate : $nowTimeString;
        $fromDate = $fromDate ? $fromDate : $nowTimeString;

        $trainerSessions = Member::select(
            'a.id',
            'a.full_name as member_name',
            'a.member_code',
            'a.photos',
            'b.start_date',
            'b.id as ts_id',
            'b.trainer_full_name',
            'b.expired_date_date',
            'b.ts_days',
            'c.registered_member_id',
            'tp.package_name',
            'max_end_date',
            'total_package_price',
            'total_admin_price',
        )
            ->from('members as a')
            ->join(DB::raw('(select a.id as id_max, b.id, trainer.full_name as trainer_full_name, b.start_date,
                            b.days as ts_days, b.trainer_package_id, tp.package_name, max(DATE_ADD(b.start_date, INTERVAL b.days DAY))
                as max_end_date, sum(b.package_price) as total_package_price,
                DATE_ADD(b.start_date, INTERVAL b.days DAY) as expired_date_date,
                sum(b.admin_price) as total_admin_price from members a
                inner join trainer_sessions b on a.id=b.member_id
                LEFT JOIN personal_trainers trainer ON b.trainer_id = trainer.id
                LEFT JOIN trainer_packages tp ON b.trainer_package_id = tp.id
                where DATE_ADD(b.start_date, INTERVAL b.days DAY) < now() group by a.id, b.id, b.days, b.start_date,
                    trainer.full_name, b.trainer_package_id, tp.package_name) as b'), function ($join) {
                $join->on('a.id', '=', 'b.id_max');
            })
            ->leftJoin(DB::raw('(select distinct member_id as registered_member_id from trainer_sessions
                                    where DATE_ADD(start_date, INTERVAL days DAY) >= now()) as c'), function ($join) {
                $join->on('a.id', '=', 'c.registered_member_id');
            })
            // ->leftJoin('trainer_packages as tp', function ($join) {
            //     $join->on('b.trainer_package_id', '=', 'tp.id')
            //         ->whereNull('tp.status');
            // })
            ->leftJoin('trainer_packages as tp', 'tp.id', '=', 'b.trainer_package_id')
            ->where('a.created_at', '>=', $fromDate)
            ->where('a.created_at', '<=', $toDate)
            ->whereNull('tp.status')
            ->whereNull('c.registered_member_id')
            ->get();

        return view('admin.trainer-session-over.excel', [
            'trainerSessions' => $trainerSessions
        ]);
    }
}
