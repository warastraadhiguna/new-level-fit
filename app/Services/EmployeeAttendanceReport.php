<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeAttendanceReport
{
    public function rows($employees, int $branch, string $from, string $to): array
    {
        $db=DB::connection('face_enrollment');$ids=$employees->pluck('id');
        $schedules=$db->table('employee_shift_schedules')->where('branch_store_id',$branch)->whereIn('employee_id',$ids)
            ->whereBetween('work_date',[$from,$to])->get()->keyBy(function($s) { return $s->employee_id.'|'.$s->work_date; });
        $records=$db->table('face_attendance_records as r')->join('face_attendance_employees as f','f.id','=','r.employee_id')
            ->where(function($q) use($branch) {
                $q->where('r.branch_store_id',$branch)->orWhereExists(function($s) use($branch) {
                    $s->selectRaw('1')->from('employee_shift_schedules as s')->whereColumn('s.employee_id','f.source_employee_id')
                        ->whereColumn('s.work_date','r.attendance_date')->where('s.branch_store_id',$branch);
                })->orWhereExists(function($a) use($branch) {
                    $a->selectRaw('1')->from('face_attendance_records as a')->whereColumn('a.employee_id','r.employee_id')
                        ->whereColumn('a.attendance_date','r.attendance_date')->where('a.branch_store_id',$branch);
                });
            })->whereIn('f.source_employee_id',$ids)->whereBetween('r.attendance_date',[$from,$to])
            ->select('r.*','f.source_employee_id')->get()->groupBy(function($r) { return $r->source_employee_id.'|'.$r->attendance_date; });
        $leaves=$db->table('employee_attendance_leaves')->whereIn('employee_id',$ids)->where('status','approved')
            ->where('start_date','<=',$to)->where('end_date','>=',$from)->get();
        $rows=[];$now=Carbon::now(config('face.attendance_timezone','Asia/Jakarta'));
        foreach ($employees as $employee) for ($day=Carbon::parse($from);$day->lte(Carbon::parse($to));$day->addDay()) {
            $date=$day->toDateString();$key=$employee->id.'|'.$date;
            if ($employee->employment_start_date && $date<$employee->employment_start_date->toDateString()) continue;
            if ($employee->resign_date && $date>$employee->resign_date->toDateString()) continue;
            $schedule=$schedules->get($key);$events=$records->get($key,collect());
            $in=$events->firstWhere('type','check_in');$out=$events->firstWhere('type','check_out');
            $leave=$leaves->first(function($l) use($employee,$date) { return (int)$l->employee_id===(int)$employee->id && $l->start_date<=$date && $l->end_date>=$date; });
            $late=(int)($in->late_minutes ?? 0);$early=(int)($out->early_leave_minutes ?? 0);
            if ($in) $status=$out ? ($late>0 ? 'Terlambat' : 'Hadir') : 'Belum pulang';
            elseif ($leave) $status=['izin'=>'Izin','cuti'=>'Cuti','sakit'=>'Sakit'][$leave->type] ?? $leave->type;
            elseif ($schedule && $schedule->is_day_off) $status='Libur';
            elseif ($schedule) $status=$now->gt(Carbon::parse($schedule->ends_at,$now->timezone)) ? 'Tidak hadir' : 'Terjadwal';
            else $status='Tanpa jadwal';
            $rows[]=['employee_id'=>$employee->id,'code'=>$employee->employee_code,'name'=>$employee->full_name,'date'=>$date,
                'shift'=>$schedule->shift_name ?? ($in && $in->schedule_id ? 'Shift tersimpan' : '-'),
                'start'=>$in->scheduled_start ?? ($schedule->starts_at ?? null),'end'=>$in->scheduled_end ?? ($schedule->ends_at ?? null),
                'check_in'=>$in->attended_at ?? null,'check_out'=>$out->attended_at ?? null,'late'=>$late,'early'=>$early,
                'work_minutes'=>$in && $out ? max(0,(int)floor((strtotime($out->attended_at)-strtotime($in->attended_at))/60)) : null,
                'status'=>$status];
        }
        return $rows;
    }
}
