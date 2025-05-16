<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class StaffExport implements FromView
{
    public function view(): View
    {
        $ptStaff = DB::table('trainer_sessions as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.old_days',
                'a.number_of_session as ts_number_of_session',
                'a.package_price as ts_package_price',
                'a.admin_price as ts_admin_price',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number as member_phone',
                'b.photos',
                'b.born',
                'c.package_name as pt_package_name',
                'c.number_of_session',
                'c.package_price',
                'd.full_name as trainer_name',
                'd.phone_number as trainer_phone',
                'g.full_name as staff_name',
                'h.full_name as fc_name',
                'h.phone_number as fc_phone_number',
                'i.name as method_payment_name',
                'cits.check_in_time',
                'cits.check_out_time'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ptld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(ptld.submission_date, INTERVAL ptld.days DAY) as expired_leave_days'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(ptld.submission_date, INTERVAL ptld.days DAY) THEN "Ended" 
                    WHEN NOW() BETWEEN ptld.submission_date AND DATE_ADD(ptld.submission_date, INTERVAL ptld.days DAY) THEN "Freeze" 
                    ELSE "No Leave Days" 
                END as leave_day_status'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as expired_date_status'),
                DB::raw('IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) as remaining_sessions'),
                DB::raw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('personal_trainers as d', 'a.trainer_id', '=', 'd.id')
            ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions where check_out_time is not null
                                    GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
            ->leftJoin(DB::raw("(select a.* from check_in_trainer_sessions a inner join (SELECT max(id) as id FROM check_in_trainer_sessions
                                    group by trainer_session_id) as b on a.id=b.id) as cits"), 'cits.trainer_session_id', '=', 'a.id')
            ->join('users as g', 'a.user_id', '=', 'g.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->join('fitness_consultants as h', 'a.fc_id', '=', 'h.id')
            ->join('method_payments as i', 'a.method_payment_id', '=', 'i.id')
            ->leftJoin('pt_leave_days as ptld', 'a.id', '=', 'ptld.trainer_session_id')
            ->whereRaw('CASE WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) > 0 THEN "Running"
                        WHEN IFNULL(a.number_of_session - e.check_in_count, a.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
            ->orderBy('d.full_name', 'asc')
            ->get();

        return view('admin.staff.excel', [
            'personalTrainers' => $ptStaff
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
