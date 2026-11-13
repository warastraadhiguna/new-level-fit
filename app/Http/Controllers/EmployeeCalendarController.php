<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeCalendarService;
use App\Support\RoleFeatureAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCalendarController extends Controller
{
    private function db() { return DB::connection('face_enrollment'); }
    private function employee(Request $r, int $id)
    {
        abort_unless($r->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
        return Employee::availableInBranch((int)$r->user()->branch_store_id)->findOrFail($id);
    }
    public function index(Request $r, int $employee)
    {
        $person=$this->employee($r,$employee);
        $r->validate(['month'=>'nullable|date_format:Y-m']);
        $month=$r->input('month') ?: Carbon::now(config('face.attendance_timezone') ?: 'Asia/Jakarta')->format('Y-m');
        $start=Carbon::createFromFormat('!Y-m',$month);$end=$start->copy()->endOfMonth();
        $schedules=$this->db()->table('employee_shift_schedules as s')->leftJoin('branch_stores as b','b.id','=','s.branch_store_id')
            ->where('s.employee_id',$employee)->whereBetween('s.work_date',[$start->toDateString(),$end->toDateString()])
            ->select('s.*','b.name as branch_name')->orderBy('s.work_date')->get()->keyBy('work_date');
        $leaves=$this->db()->table('employee_attendance_leaves')->where('employee_id',$employee)->whereIn('status',['pending','approved'])
            ->where('start_date','<=',$end->toDateString())->where('end_date','>=',$start->toDateString())->orderByDesc('id')->get();
        $recordDates=$this->db()->table('face_attendance_records as r')->join('face_attendance_employees as f','f.id','=','r.employee_id')
            ->where('f.source_employee_id',$employee)->whereBetween('r.attendance_date',[$start->toDateString(),$end->toDateString()])
            ->distinct()->pluck('r.attendance_date')->all();
        $days=[];
        for ($day=$start->copy();$day->lte($end);$day->addDay()) {
            $date=$day->toDateString();$schedule=$schedules->get($date);
            $leave=$leaves->first(fn($l)=>$l->start_date<=$date && $l->end_date>=$date);
            $recorded=in_array($date,$recordDates,true);$foreign=$schedule && (int)$schedule->branch_store_id!==(int)$r->user()->branch_store_id;
            $days[]=['date'=>$date,'day'=>$day->day,'schedule'=>$schedule,'leave'=>$leave,'recorded'=>$recorded,'foreign'=>$foreign,
                'locked'=>!$person->is_active || $recorded || $foreign || (bool)$leave];
        }
        return view('admin.layouts.wrapper',[
            'title'=>'Schedule '.$person->full_name,'content'=>'admin.employee-attendance.calendar','employee'=>$person,
            'month'=>$month,'monthStart'=>$start,'monthEnd'=>$end,'days'=>$days,'attendanceAccess'=>app(RoleFeatureAccess::class),
            'templates'=>$this->db()->table('employee_shift_templates')->where('branch_store_id',$r->user()->branch_store_id)->where('is_active',true)->orderBy('name')->get(),
        ]);
    }
    private function data(Request $r, bool $leave): array
    {
        $data=$r->validate([
            'month'=>'required|date_format:Y-m','dates'=>'required|array|min:1|max:31','dates.*'=>'required|date_format:Y-m-d|distinct',
            'operation'=>$leave ? 'required|in:izin,cuti,sakit' : 'required|in:shift,libur',
            'template_id'=>'nullable|integer','note'=>$leave ? 'required|string|max:2000' : 'nullable|string|max:2000',
        ]);
        foreach ($data['dates'] as $date) if (substr($date,0,7)!==$data['month']) {
            throw ValidationException::withMessages(['dates'=>'Tanggal pilihan harus berada dalam bulan yang ditampilkan.']);
        }
        return $data;
    }
    public function schedule(Request $r, int $employee, EmployeeCalendarService $service)
    {
        $this->employee($r,$employee);$data=$this->data($r,false);$service->schedule($r->user(),$employee,$data);
        return redirect()->route('employee-schedules.calendar',[$employee,'month'=>$data['month']])->with('message','Jadwal tanggal pilihan berhasil disimpan.');
    }
    public function leave(Request $r, int $employee, EmployeeCalendarService $service)
    {
        $this->employee($r,$employee);$data=$this->data($r,true);$service->leave($r->user(),$employee,$data);
        return redirect()->route('employee-schedules.calendar',[$employee,'month'=>$data['month']])->with('message','Pengajuan izin/cuti tanggal pilihan dibuat dan menunggu persetujuan di Day Off.');
    }
}
