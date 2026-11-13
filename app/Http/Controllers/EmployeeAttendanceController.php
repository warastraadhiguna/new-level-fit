<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeAttendanceReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmployeeAttendanceController extends Controller
{
    private function db() { return DB::connection('face_enrollment'); }
    private function branch(Request $r): int
    {
        abort_unless($r->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
        return (int)$r->user()->branch_store_id;
    }
    private function employees(Request $r) { return Employee::availableInBranch($this->branch($r)); }
    private function page(string $view, array $data=[])
    {
        return view('admin.layouts.wrapper',$data+['title'=>'Absensi Pegawai','content'=>'admin.employee-attendance.'.$view,
            'attendanceAccess'=>app(\App\Support\RoleFeatureAccess::class)]);
    }
    private function fail(string $message): void { throw ValidationException::withMessages(['attendance'=>$message]); }
    private function lockEmployee(Request $r, int $id, bool $active=true)
    {
        $query=$this->employees($r);
        if ($active) $query->where('is_active',true);
        else $query->withTrashed();
        return $query->lockForUpdate()->findOrFail($id);
    }
    private function hasRecords(int $id, string $from, string $to): bool
    {
        return $this->db()->table('face_attendance_records as r')->join('face_attendance_employees as f','f.id','=','r.employee_id')
            ->where('f.source_employee_id',$id)->whereBetween('r.attendance_date',[$from,$to])->exists();
    }
    public function templates(Request $r)
    {
        return $this->page('templates',['templates'=>$this->db()->table('employee_shift_templates')->where('branch_store_id',$this->branch($r))->orderBy('name')->get()]);
    }
    private function templateData(Request $r): array
    {
        $data=$r->validate(['name'=>'required|string|max:100','start_time'=>'required|date_format:H:i','end_time'=>'required|date_format:H:i|different:start_time',
            'grace_minutes'=>'required|integer|min:0|max:180','is_active'=>'required|boolean']);
        return $data;
    }
    public function storeTemplate(Request $r)
    {
        $this->db()->table('employee_shift_templates')->insert($this->templateData($r)+['branch_store_id'=>$this->branch($r),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('message','Template shift ditambahkan.');
    }
    public function updateTemplate(Request $r, int $id)
    {
        $data=$this->templateData($r);
        $this->db()->transaction(function() use($r,$id,$data) {
            $template=$this->db()->table('employee_shift_templates')->where('branch_store_id',$this->branch($r))->where('id',$id)->lockForUpdate()->first();abort_unless($template,404);
            $this->db()->table('employee_shift_templates')->where('id',$id)->update($data+['updated_at'=>now()]);
        });
        return back()->with('message','Template diubah. Jadwal yang sudah dibuat tetap memakai jam sebelumnya.');
    }
    public function destroyTemplate(Request $r, int $id)
    {
        $this->db()->transaction(function() use($r,$id) {
            $template=$this->db()->table('employee_shift_templates')->where('branch_store_id',$this->branch($r))->where('id',$id)->lockForUpdate()->first();abort_unless($template,404);
            if ($this->db()->table('employee_shift_schedules')->where('template_id',$id)->exists()) $this->fail('Template sudah dipakai jadwal. Nonaktifkan template melalui Edit.');
            $this->db()->table('employee_shift_templates')->where('id',$id)->delete();
        });
        return back()->with('message','Template dihapus.');
    }
    private function range(Request $r): array
    {
        $r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d','employee_id'=>'nullable|integer']);
        $from=$r->input('from',Carbon::now(config('face.attendance_timezone'))->startOfMonth()->toDateString());
        $to=$r->input('to',Carbon::now(config('face.attendance_timezone'))->endOfMonth()->toDateString());
        if (!$from || !$to || $to<$from || Carbon::parse($from)->diffInDays(Carbon::parse($to))>92) $this->fail('Pilih rentang tanggal maksimal 93 hari.');
        return [$from,$to];
    }
    public function schedules(Request $r)
    {
        [$from,$to]=$this->range($r);
        $query=$this->db()->table('employee_shift_schedules as s')->join('employees as e','e.id','=','s.employee_id')->where('s.branch_store_id',$this->branch($r))
            ->whereBetween('work_date',[$from,$to])->select('s.*','e.full_name','e.employee_code');
        if ($r->filled('employee_id')) $query->where('employee_id',$r->employee_id);
        return $this->page('schedules',['from'=>$from,'to'=>$to,'employees'=>$this->employees($r)->where('is_active',true)->orderBy('full_name')->get(),
            'templates'=>$this->db()->table('employee_shift_templates')->where('branch_store_id',$this->branch($r))->where('is_active',true)->orderBy('name')->get(),
            'schedules'=>$query->orderBy('work_date')->orderBy('e.full_name')->paginate(50)->withQueryString()]);
    }
    public function storeSchedules(Request $r)
    {
        $data=$r->validate(['employee_ids'=>'required|array|min:1|max:300','employee_ids.*'=>'required|integer|distinct',
            'start_date'=>'required|date_format:Y-m-d','end_date'=>'required|date_format:Y-m-d|after_or_equal:start_date',
            'weekdays'=>'required|array|min:1','weekdays.*'=>'integer|between:1,7','template_id'=>'nullable|integer',
            'is_day_off'=>'required|boolean','note'=>'nullable|string|max:2000']);
        if (Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']))>92) $this->fail('Rentang jadwal maksimal 93 hari.');
        $this->db()->transaction(function() use($r,$data) {
            $template=null;
            if (!$data['is_day_off']) {
                $template=$this->db()->table('employee_shift_templates')->where('id',$data['template_id'] ?? 0)->where('branch_store_id',$this->branch($r))->where('is_active',true)->lockForUpdate()->first();
                if (!$template) $this->fail('Pilih template shift aktif di cabang ini.');
            }
            $ids=$data['employee_ids'];sort($ids,SORT_NUMERIC);$count=0;
            foreach ($ids as $id) {
                $this->lockEmployee($r,$id);
                for ($day=Carbon::parse($data['start_date']);$day->lte(Carbon::parse($data['end_date']));$day->addDay()) {
                    if (!in_array($day->isoWeekday(),$data['weekdays'])) continue;
                    $date=$day->toDateString();
                    $existing=$this->db()->table('employee_shift_schedules')->where('employee_id',$id)->where('work_date',$date)->first();
                    if ($existing) $this->fail('Sudah ada jadwal pegawai pada '.$date.'. Hapus jadwal lama terlebih dahulu jika ingin menggantinya.');
                    if ($this->hasRecords($id,$date,$date)) $this->fail('Jadwal tidak dapat ditambahkan setelah pegawai absen pada '.$date.'.');
                    $start=$template ? Carbon::parse($date.' '.$template->start_time) : null;
                    $end=$template ? Carbon::parse($date.' '.$template->end_time) : null;
                    if ($start && $end->lte($start)) $end->addDay();
                    if ($start && $this->db()->table('employee_shift_schedules')->where('employee_id',$id)->where('is_day_off',false)
                        ->where('starts_at','<',$end)->where('ends_at','>',$start)->exists()) $this->fail('Jam shift bertabrakan dengan jadwal lain.');
                    $this->db()->table('employee_shift_schedules')->insert(['employee_id'=>$id,'branch_store_id'=>$this->branch($r),'work_date'=>$date,
                        'template_id'=>$template->id ?? null,'shift_name'=>$template->name ?? 'Libur','starts_at'=>$start,'ends_at'=>$end,
                        'grace_minutes'=>$template->grace_minutes ?? 0,'is_day_off'=>(bool)$data['is_day_off'],'note'=>$data['note'] ?? null,
                        'created_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);$count++;
                }
            }
            if (!$count) $this->fail('Tidak ada tanggal yang cocok dengan hari pilihan.');
        });
        return back()->with('message','Jadwal pegawai berhasil ditambahkan.');
    }
    public function destroySchedule(Request $r, int $id)
    {
        $this->db()->transaction(function() use($r,$id) {
            $schedule=$this->db()->table('employee_shift_schedules')->where('branch_store_id',$this->branch($r))->where('id',$id)->first();abort_unless($schedule,404);
            $this->lockEmployee($r,$schedule->employee_id,false);
            if ($this->hasRecords($schedule->employee_id,$schedule->work_date,$schedule->work_date)) $this->fail('Jadwal sudah memiliki absensi dan tidak dapat dihapus.');
            $this->db()->table('employee_shift_schedules')->where('id',$id)->delete();
        });
        return back()->with('message','Jadwal dihapus.');
    }
    public function leaves(Request $r)
    {
        $r->validate(['status'=>'nullable|in:pending,approved,rejected,cancelled']);
        $query=$this->db()->table('employee_attendance_leaves as l')->join('employees as e','e.id','=','l.employee_id')
            ->where('l.branch_store_id',$this->branch($r))->select('l.*','e.full_name','e.employee_code');
        if ($r->filled('status')) $query->where('l.status',$r->status);
        return $this->page('leaves',['employees'=>$this->employees($r)->where('is_active',true)->orderBy('full_name')->get(),
            'leaves'=>$query->orderByDesc('l.id')->paginate(30)->withQueryString()]);
    }
    public function storeLeave(Request $r)
    {
        $data=$r->validate(['employee_id'=>'required|integer','type'=>'required|in:izin,cuti,sakit','start_date'=>'required|date_format:Y-m-d',
            'end_date'=>'required|date_format:Y-m-d|after_or_equal:start_date','reason'=>'required|string|max:3000']);
        if (Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']))>365) $this->fail('Rentang izin/cuti maksimal 366 hari.');
        $this->db()->transaction(function() use($r,$data) {
            $this->lockEmployee($r,$data['employee_id']);
            $this->assertLeaveAvailable($data['employee_id'],$data['start_date'],$data['end_date']);
            $this->db()->table('employee_attendance_leaves')->insert($data+['branch_store_id'=>$this->branch($r),'status'=>'pending',
                'created_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('message','Pengajuan dicatat dan menunggu persetujuan.');
    }
    private function assertLeaveAvailable(int $employee, string $from, string $to, ?int $except=null): void
    {
        $q=$this->db()->table('employee_attendance_leaves')->where('employee_id',$employee)->whereIn('status',['pending','approved'])
            ->where('start_date','<=',$to)->where('end_date','>=',$from);
        if ($except) $q->where('id','<>',$except);
        if ($q->exists()) $this->fail('Ada pengajuan izin/cuti lain pada rentang tanggal tersebut.');
        if ($this->hasRecords($employee,$from,$to)) $this->fail('Pegawai sudah memiliki absensi pada rentang tanggal tersebut.');
    }
    public function reviewLeave(Request $r, int $id)
    {
        $data=$r->validate(['status'=>'required|in:approved,rejected,cancelled','review_note'=>'nullable|string|max:3000']);
        $this->db()->transaction(function() use($r,$id,$data) {
            $leave=$this->db()->table('employee_attendance_leaves')->where('branch_store_id',$this->branch($r))->where('id',$id)->first();abort_unless($leave,404);
            $this->lockEmployee($r,$leave->employee_id,$data['status']==='approved');
            $leave=$this->db()->table('employee_attendance_leaves')->where('id',$id)->lockForUpdate()->first();
            if (!($leave->status==='pending' || ($leave->status==='approved' && $data['status']==='cancelled'))) $this->fail('Status pengajuan sudah berubah. Muat ulang halaman.');
            if ($data['status']==='approved') $this->assertLeaveAvailable($leave->employee_id,$leave->start_date,$leave->end_date,$id);
            $this->db()->table('employee_attendance_leaves')->where('id',$id)->update($data+['reviewed_by'=>$r->user()->id,'reviewed_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('message','Status pengajuan diperbarui.');
    }
    public function report(Request $r, EmployeeAttendanceReport $report)
    {
        [$from,$to]=$this->range($r);$employees=$this->employees($r)->withTrashed()->orderBy('full_name')->get();
        $selected=$r->filled('employee_id') ? $employees->where('id',(int)$r->employee_id) : $employees;
        $rows=$report->rows($selected,$this->branch($r),$from,$to);
        $r->validate(['status'=>'nullable|in:Hadir,Terlambat,Belum pulang,Izin,Cuti,Sakit,Libur,Tidak hadir,Terjadwal,Tanpa jadwal']);
        if ($r->filled('status')) $rows=array_values(array_filter($rows,function($row) use($r) { return $row['status']===$r->status; }));
        if ($r->routeIs('employee-attendance.report.export')) return response()->streamDownload(function() use($rows) {
            $file=fopen('php://output','w');fwrite($file,"\xEF\xBB\xBF");
            fputcsv($file,['Kode','Pegawai','Tanggal kerja','Shift','Mulai shift','Akhir shift','Masuk','Pulang','Terlambat (menit)','Pulang awal (menit)','Durasi (menit)','Status']);
            foreach ($rows as $row) { unset($row['employee_id'],$row['record_ids']);fputcsv($file,array_map(function($value) {
                return is_string($value) && preg_match('/^[=+@\-\t\r\n]/',$value) ? "'".$value : $value;
            },array_values($row))); }fclose($file);
        },'rekap-absensi-'.$from.'-'.$to.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
        $summary=['hadir'=>0,'terlambat'=>0,'absen'=>0,'izin'=>0,'late'=>0,'early'=>0,'work'=>0];
        foreach ($rows as $row) {
            if ($row['check_in']) $summary['hadir']++;
            if ($row['late']>0) $summary['terlambat']++;
            if ($row['status']==='Tidak hadir') $summary['absen']++;
            if (in_array($row['status'],['Izin','Cuti','Sakit'])) $summary['izin']++;
            $summary['late']+=$row['late'];$summary['early']+=$row['early'];$summary['work']+=$row['work_minutes'] ?? 0;
        }
        $r->validate(['page'=>'nullable|integer|min:1']);$page=(int)$r->input('page',1);
        $paginator=new \Illuminate\Pagination\LengthAwarePaginator(array_slice($rows,($page-1)*50,50),count($rows),50,$page,['path'=>$r->url(),'query'=>$r->query()]);
        return $this->page('report',compact('from','to','employees','summary')+['rows'=>$paginator]);
    }

    public function destroyAttendance(Request $r, int $employee, string $date)
    {
        $r->merge(['work_date'=>$date]);
        $data=$r->validate(['work_date'=>'required|date_format:Y-m-d',
            'record_ids'=>'required|array|min:1|max:10','record_ids.*'=>'required|integer|min:1|distinct',
            'from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d',
            'employee_id'=>'nullable|integer|min:1','status'=>'nullable|string|max:30','page'=>'nullable|integer|min:1']);
        $records=$this->db()->transaction(function() use($r,$employee,$date,$data) {
            // Same source-employee lock as face check-in/out, including inactive/deleted employees.
            $this->lockEmployee($r,$employee,false);
            $profiles=$this->db()->table('face_attendance_employees')->where('source_employee_id',$employee)->pluck('id');
            $records=$this->db()->table('face_attendance_records')->whereIn('employee_id',$profiles)
                ->where('attendance_date',$date)->lockForUpdate()->get();
            $branch=$this->branch($r);
            $visible=$records->contains(fn($record)=>(int)$record->branch_store_id===$branch)
                || $this->db()->table('employee_shift_schedules')->where('employee_id',$employee)->where('work_date',$date)->where('branch_store_id',$branch)->exists();
            abort_unless($visible && $records->isNotEmpty(),404);
            $expected=array_map('intval',$data['record_ids']);sort($expected,SORT_NUMERIC);
            $actual=$records->pluck('id')->map(fn($id)=>(int)$id)->sort()->values()->all();
            if ($expected!==$actual) $this->fail('Data absensi sudah berubah. Muat ulang Attendance Report sebelum menghapus.');
            $this->db()->table('face_attendance_records')->whereIn('id',$actual)->delete();
            return $records->map(fn($record)=>(array)$record)->all();
        });
        Log::info('Employee attendance deleted',['actor_id'=>$r->user()->id,'branch_store_id'=>$this->branch($r),
            'employee_id'=>$employee,'work_date'=>$date,'records'=>$records]);
        return redirect()->route('employee-attendance.report',$r->only(['from','to','employee_id','status','page']))
            ->with('message','Absensi masuk/pulang pada tanggal kerja tersebut berhasil dihapus. Jadwal pegawai tetap tersimpan.');
    }
}
