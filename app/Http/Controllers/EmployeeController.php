<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    private function employees(Request $request)
    {
        abort_unless($request->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
        return Employee::where('branch_store_id',$request->user()->branch_store_id);
    }

    public function index(Request $request)
    {
        $request->validate(['search'=>'nullable|string|max:150','per_page'=>'nullable|in:10,25,50,100']);
        $query=$this->employees($request);
        if ($request->filled('search')) {
            $search=$request->input('search');
            $query->where(function($q) use($search) { $q->where('full_name','like','%'.$search.'%')->orWhere('employee_code','like','%'.$search.'%'); });
        }
        return view('admin.layouts.wrapper',[
            'title'=>'Data Pegawai','content'=>'admin.employees.index',
            'employees'=>$query->orderBy('full_name')->paginate((int)$request->input('per_page',10))->withQueryString(),
        ]);
    }

    private function validated(Request $request, ?Employee $employee=null): array
    {
        return $request->validate([
            'employee_code'=>['required','string','max:50',Rule::unique('face_enrollment.employees','employee_code')->ignore($employee ? $employee->id : null)],
            'full_name'=>'required|string|max:150',
            'position'=>'nullable|string|max:100','phone'=>'nullable|string|max:30',
            'is_active'=>'required|boolean',
        ]);
    }

    public function store(Request $request)
    {
        $this->employees($request);
        Employee::create($this->validated($request)+['branch_store_id'=>$request->user()->branch_store_id]);
        return redirect()->route('employees.index')->with('message','Pegawai berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        return view('admin.layouts.wrapper',[
            'title'=>'Ubah Pegawai','content'=>'admin.employees.edit',
            'employee'=>$this->employees($request)->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee=$this->employees($request)->findOrFail($id);
        $data=$this->validated($request,$employee);
        DB::connection('face_enrollment')->transaction(function() use($request,$employee,$data) {
            $current=$this->employees($request)->where('id',$employee->id)->lockForUpdate()->firstOrFail();
            $current->update($data);
            $branch=DB::connection('face_enrollment')->table('branch_stores')->where('id',$current->branch_store_id)->value('name');
            DB::connection('face_enrollment')->table('face_attendance_employees')->where('source_employee_id',$current->id)->update([
                'name'=>$current->full_name,'department'=>($current->position ?: 'Pegawai').($branch ? ' · '.$branch : ''),'updated_at'=>now(),
            ]);
        });
        return redirect()->route('employees.index')->with('message','Pegawai berhasil diubah.');
    }

    public function destroy(Request $request, $id)
    {
        DB::connection('face_enrollment')->transaction(function() use($request,$id) {
            $this->employees($request)->where('id',$id)->lockForUpdate()->firstOrFail()->delete();
        });
        return redirect()->route('employees.index')->with('message','Pegawai dihapus sementara dan tidak dapat absen.');
    }
}
