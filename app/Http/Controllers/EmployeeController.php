<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    private function employees(Request $request)
    {
        abort_unless($request->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
        return Employee::availableInBranch((int)$request->user()->branch_store_id);
    }

    public function index(Request $request)
    {
        $request->validate(['search'=>'nullable|string|max:150','per_page'=>'nullable|in:10,25,50,100','edit_employee'=>'nullable|integer|min:1']);
        $query=$this->employees($request);
        if ($request->filled('search')) {
            $search=$request->input('search');
            $query->where(function($q) use($search) { $q->where('full_name','like','%'.$search.'%')->orWhere('employee_code','like','%'.$search.'%'); });
        }
        $employees=$query->orderBy('full_name')->paginate((int)$request->input('per_page',10))->withQueryString();
        $editId=$request->input('edit_employee') ?: $request->session()->getOldInput('_employee_id');
        $editingEmployee=$editId ? $this->employees($request)->findOrFail($editId) : null;
        return view('admin.layouts.wrapper',[
            'title'=>'Data Pegawai','content'=>'admin.employees.index',
            'birthdayMessages'=>$this->birthdayMessages($request),
            'employees'=>$employees,'editingEmployee'=>$editingEmployee,
        ]);
    }

    private function birthdayMessages(Request $request): array
    {
        $messages=[0=>[],1=>[],2=>[]];
        $today=\Carbon\Carbon::today('Asia/Jakarta');
        $dates=[];
        for ($day=0;$day<=2;$day++) {
            $date=$today->copy()->addDays($day);
            $dates[$date->format('m-d')]=$day;
            // Celebrate leap-day birthdays on March 1 in non-leap years.
            if ($date->format('m-d')==='03-01' && !$date->isLeapYear()) $dates['02-29']=$day;
        }
        $employees=$this->employees($request)->where('is_active',true)->whereNotNull('born')
            ->where(function($query) use($dates) {
                foreach ($dates as $date=>$day) {
                    [$month,$dateDay]=explode('-',$date);
                    $query->orWhere(function($birthday) use($month,$dateDay) {
                        $birthday->whereMonth('born',(int)$month)->whereDay('born',(int)$dateDay);
                    });
                }
            })->orderBy('full_name')->get(['id','full_name','born']);
        foreach ($employees as $employee) {
            $messages[$dates[$employee->born->format('m-d')]][$employee->id]=$employee->full_name;
        }
        return $messages;
    }

    private function validated(Request $request, ?Employee $employee=null): array
    {
        $data=$request->validate([
            'employee_code'=>['required','string','max:50',Rule::unique('face_enrollment.employees','employee_code')->ignore($employee ? $employee->id : null)],
            'full_name'=>'required|string|max:150',
            'born'=>['nullable','date_format:Y-m-d','before_or_equal:'.\Carbon\Carbon::today('Asia/Jakarta')->toDateString()],
            'position'=>'nullable|string|max:100','phone'=>'nullable|string|max:30',
            'is_active'=>'required|boolean',
            'all_branch'=>'sometimes|boolean',
            'position_category'=>['nullable',Rule::in(array_keys(Employee::POSITION_CATEGORIES))],
            'employment_start_date'=>'nullable|date_format:Y-m-d',
            'resign_date'=>'nullable|date_format:Y-m-d',
            'note'=>'nullable|string|max:5000',
            'photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'ktp_photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $start=array_key_exists('employment_start_date',$data) ? $data['employment_start_date'] : optional($employee)->employment_start_date;
        $end=array_key_exists('resign_date',$data) ? $data['resign_date'] : optional($employee)->resign_date;
        if ($start && $end && \Carbon\Carbon::parse($end)->lt(\Carbon\Carbon::parse($start))) {
            throw ValidationException::withMessages(['resign_date'=>'Tanggal resign tidak boleh sebelum tanggal mulai kerja.']);
        }
        unset($data['photo'],$data['ktp_photo']);
        return $data;
    }

    public function store(Request $request)
    {
        $this->employees($request);
        $this->saveEmployee($request,$this->validated($request));
        return redirect()->route('employees.index')->with('message','Pegawai berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        $this->employees($request)->findOrFail($id);
        $request->merge(['edit_employee'=>$id]);
        return $this->index($request);
    }

    public function update(Request $request, $id)
    {
        $employee=$this->employees($request)->findOrFail($id);
        $data=$this->validated($request,$employee);
        $this->saveEmployee($request,$data,$employee);
        return redirect()->route('employees.index')->with('message','Pegawai berhasil diubah.');
    }

    private function saveEmployee(Request $request, array $data, ?Employee $employee=null): void
    {
        $uploaded=[];$replaced=[];
        try {
            DB::connection('face_enrollment')->transaction(function() use($request,$data,$employee,&$uploaded,&$replaced) {
                $current=$employee ? $this->employees($request)->where('id',$employee->id)->lockForUpdate()->firstOrFail() : new Employee();
                foreach (['photo'=>'photo_path','ktp_photo'=>'ktp_photo_path'] as $input=>$column) {
                    if (!$request->hasFile($input)) continue;
                    $path=$request->file($input)->store('employees/'.$input,'local');
                    if (!$path) throw new \RuntimeException('Foto pegawai gagal disimpan.');
                    $uploaded[]=$path;
                    if ($current->$column) $replaced[]=$current->$column;
                    $data[$column]=$path;
                }
                $current->fill($data);
                if (!$employee) $current->branch_store_id=$request->user()->branch_store_id;
                $current->save();
                if (!$employee) return;
                $branch=DB::connection('face_enrollment')->table('branch_stores')->where('id',$current->branch_store_id)->value('name');
                DB::connection('face_enrollment')->table('face_attendance_employees')->where('source_employee_id',$current->id)->update([
                    'name'=>$current->full_name,'department'=>($current->position ?: 'Pegawai').($branch ? ' · '.$branch : ''),'updated_at'=>now(),
                ]);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($uploaded);
            throw $error;
        }
        Storage::disk('local')->delete($replaced);
    }

    public function photo(Request $request, $id, string $kind)
    {
        abort_unless(in_array($kind,['photo','ktp'],true),404);
        $employee=$this->employees($request)->findOrFail($id);
        $path=$kind==='photo' ? $employee->photo_path : $employee->ktp_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path),404);
        return response()->file(Storage::disk('local')->path($path),[
            'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        DB::connection('face_enrollment')->transaction(function() use($request,$id) {
            $this->employees($request)->where('id',$id)->lockForUpdate()->firstOrFail()->delete();
        });
        return redirect()->route('employees.index')->with('message','Pegawai dihapus sementara dan tidak dapat absen.');
    }
}
