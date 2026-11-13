<?php
namespace App\Services;

use App\Models\Employee;
use App\Support\RoleFeatureAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCalendarService
{
    private function db() { return DB::connection('face_enrollment'); }
    private function fail(string $message): void { throw ValidationException::withMessages(['calendar'=>$message]); }
    private function employee($user, int $id)
    {
        return Employee::availableInBranch((int)$user->branch_store_id)->where('is_active',true)->lockForUpdate()->findOrFail($id);
    }
    private function checkDates(int $employee, array $dates): void
    {
        $used=$this->db()->table('face_attendance_records as r')->join('face_attendance_employees as f','f.id','=','r.employee_id')
            ->where('f.source_employee_id',$employee)->whereIn('r.attendance_date',$dates)->exists();
        if ($used) $this->fail('Tanggal pilihan sudah memiliki absensi. Jadwal/izin tidak dapat diubah.');
        foreach ($dates as $date) {
            if ($this->db()->table('employee_attendance_leaves')->where('employee_id',$employee)->whereIn('status',['pending','approved'])
                ->where('start_date','<=',$date)->where('end_date','>=',$date)->exists()) $this->fail('Tanggal '.$date.' sudah memiliki pengajuan izin/cuti aktif.');
        }
    }

    public function schedule($user, int $employee, array $data): void
    {
        $this->db()->transaction(function() use($user,$employee,$data) {
            $template=null;
            if ($data['operation']==='shift') {
                $template=$this->db()->table('employee_shift_templates')->where('branch_store_id',$user->branch_store_id)
                    ->where('id',$data['template_id'] ?? 0)->where('is_active',true)->lockForUpdate()->first();
                if (!$template) $this->fail('Pilih template shift aktif di cabang login.');
            }
            $this->employee($user,$employee);
            $this->checkDates($employee,$data['dates']);
            $existing=$this->db()->table('employee_shift_schedules')->where('employee_id',$employee)->whereIn('work_date',$data['dates'])->lockForUpdate()->get();
            if ($existing->contains(fn($s)=>(int)$s->branch_store_id!==(int)$user->branch_store_id)) $this->fail('Ada jadwal dari cabang lain pada tanggal pilihan.');
            if ($existing->isNotEmpty() && !app(RoleFeatureAccess::class)->allowsRoute($user,'employee-schedules.destroy')) {
                $this->fail('Mengganti jadwal yang sudah ada memerlukan izin Delete pada Jadwal Shift Pegawai.');
            }
            // The whole selection is replaced atomically; unrelated dates stay untouched.
            $this->db()->table('employee_shift_schedules')->whereIn('id',$existing->pluck('id'))->delete();
            $dates=$data['dates'];sort($dates);
            foreach ($dates as $date) {
                $start=$template ? Carbon::parse($date.' '.$template->start_time) : null;
                $end=$template ? Carbon::parse($date.' '.$template->end_time) : null;
                if ($start && $end->lte($start)) $end->addDay();
                if ($start && $this->db()->table('employee_shift_schedules')->where('employee_id',$employee)->where('is_day_off',false)
                    ->where('starts_at','<',$end)->where('ends_at','>',$start)->exists()) $this->fail('Shift pada '.$date.' bertabrakan dengan jadwal lain.');
                $this->db()->table('employee_shift_schedules')->insert([
                    'employee_id'=>$employee,'branch_store_id'=>$user->branch_store_id,'work_date'=>$date,'template_id'=>$template->id ?? null,
                    'shift_name'=>$template->name ?? 'Libur','starts_at'=>$start,'ends_at'=>$end,'grace_minutes'=>$template->grace_minutes ?? 0,
                    'is_day_off'=>$data['operation']==='libur','note'=>$data['note'] ?? null,'created_by'=>$user->id,'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        });
    }

    public function leave($user, int $employee, array $data): void
    {
        $this->db()->transaction(function() use($user,$employee,$data) {
            $this->employee($user,$employee);$this->checkDates($employee,$data['dates']);
            if ($this->db()->table('employee_shift_schedules')->where('employee_id',$employee)->whereIn('work_date',$data['dates'])
                ->where('branch_store_id','<>',$user->branch_store_id)->exists()) $this->fail('Ada jadwal cabang lain pada tanggal pilihan. Ajukan dari cabang jadwal tersebut.');
            // Group consecutive selected days only; unchecked days are never included.
            $dates=$data['dates'];sort($dates);$ranges=[];
            foreach ($dates as $date) {
                $last=count($ranges)-1;
                if ($last>=0 && Carbon::parse($ranges[$last][1])->addDay()->toDateString()===$date) $ranges[$last][1]=$date;
                else $ranges[]=[$date,$date];
            }
            foreach ($ranges as [$start,$end]) $this->db()->table('employee_attendance_leaves')->insert([
                'employee_id'=>$employee,'branch_store_id'=>$user->branch_store_id,'type'=>$data['operation'],
                'start_date'=>$start,'end_date'=>$end,'reason'=>$data['note'],'status'=>'pending','created_by'=>$user->id,'created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }
}
