<?php
namespace App\Http\Controllers;

use App\Models\{Employee,EmployeePositionCategory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeePositionCategoryController extends Controller
{
    private function ready(Request $r): void
    {
        abort_unless($r->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
        abort_unless(EmployeePositionCategory::ready(),503,'Jalankan migration atau SQL kategori jabatan terlebih dahulu.');
    }

    public function index(Request $r)
    {
        $this->ready($r);
        return view('admin.layouts.wrapper',['title'=>'Kategori Jabatan','content'=>'admin.employee-positions.index',
            'categories'=>EmployeePositionCategory::orderBy('name')->get()]);
    }

    private function validated(Request $r, bool $creating=false): array
    {
        $rules=['name'=>'required|string|max:100','is_active'=>'required|boolean'];
        if ($creating) {
            $r->merge(['code'=>is_string($r->code) ? strtoupper(trim($r->code)) : $r->code]);
            $rules['code']=['required','string','max:20','regex:/^[A-Z0-9_]+$/',Rule::unique('face_enrollment.employee_position_categories','code')];
        } else $rules['code']='prohibited';
        return $r->validate($rules);
    }

    public function store(Request $r)
    {
        $this->ready($r);EmployeePositionCategory::create($this->validated($r,true));
        return redirect()->route('employee-positions.index')->with('message','Kategori jabatan ditambahkan.');
    }

    public function update(Request $r, int $id)
    {
        $this->ready($r);$data=$this->validated($r);
        DB::connection('face_enrollment')->transaction(function() use($id,$data) {
            EmployeePositionCategory::where('id',$id)->lockForUpdate()->firstOrFail()->update($data);
        });
        return redirect()->route('employee-positions.index')->with('message','Kategori jabatan diperbarui.');
    }

    public function destroy(Request $r, int $id)
    {
        $this->ready($r);
        DB::connection('face_enrollment')->transaction(function() use($id) {
            $category=EmployeePositionCategory::where('id',$id)->lockForUpdate()->firstOrFail();
            if (Employee::withTrashed()->where('position_category',$category->code)->exists()) {
                throw ValidationException::withMessages(['category'=>'Kategori masih digunakan pegawai, termasuk pegawai yang dihapus sementara. Nonaktifkan kategori atau pindahkan jabatan pegawai terlebih dahulu.']);
            }
            $category->delete();
        });
        return redirect()->route('employee-positions.index')->with('message','Kategori jabatan dihapus.');
    }
}
