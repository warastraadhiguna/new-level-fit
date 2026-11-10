<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\{ApplicationAccess,HandlesDuplicateStaffEmail};
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};

abstract class ScopedRoleAccountController extends Controller
{
    use HandlesDuplicateStaffEmail;
    abstract protected function accountRole(): string;
    abstract protected function routePrefix(): string;

    public function home(Request $request)
    {
        abort_unless(strtoupper((string)$request->user()->role)===$this->accountRole(),403);
        return view('admin.layouts.wrapper',['title'=>$this->accountRole(),'content'=>'admin.'.$this->routePrefix().'.home']);
    }
    private function accounts(Request $request)
    {
        abort_unless($request->user()->branch_store_id,403,'Cabang aktif belum ditentukan.');
        return User::where('role',$this->accountRole())->where('branch_store_id',$request->user()->branch_store_id);
    }
    private function validateAccount(Request $request, ?User $account=null): array
    {
        $this->normalizeStaffEmail($request);
        return $request->validate([
            'full_name'=>'required|string|max:200','email'=>$this->staffEmailRules($account ? $account->id : null),
            'gender'=>'required|in:Male,Female',
            'password'=>$account ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
        ],$this->staffEmailValidationMessages());
    }
    private function grantManagement(User $user): void
    {
        if (!Schema::hasTable('user_application_access')) return;
        $user->applicationAccesses()->update(['is_active'=>false]);
        $user->applicationAccesses()->updateOrCreate(['application_code'=>ApplicationAccess::MANAGEMENT],['is_active'=>true]);
    }
    public function index(Request $request)
    {
        $this->accounts($request);
        return redirect()->route('staff.index',['page'=>$this->routePrefix()]);
    }
    public function store(Request $request)
    {
        $this->accounts($request);$data=$this->validateAccount($request);
        unset($data['password_confirmation']);$data['password']=bcrypt($data['password']);
        try {
            DB::transaction(function() use($request,$data) {
                $user=User::create($data+['role'=>$this->accountRole(),'branch_store_id'=>$request->user()->branch_store_id]);
                $this->grantManagement($user);
            });
        } catch (QueryException $error) {
            if (!$this->isDuplicateStaffEmailException($error)) throw $error;
            return back()->withErrors(['email'=>$this->duplicateStaffEmailMessage()])->withInput($request->except('password','password_confirmation'));
        }
        return redirect()->route('staff.index',['page'=>$this->routePrefix()])->with('message','Akun '.$this->accountRole().' berhasil ditambahkan.');
    }
    public function edit(Request $request, $id)
    {
        $account=$this->accounts($request)->findOrFail($id);
        return redirect()->route('staff.index',['page'=>$this->routePrefix(),'edit_account'=>$account->id]);
    }
    public function update(Request $request, $id)
    {
        $account=$this->accounts($request)->findOrFail($id);$data=$this->validateAccount($request,$account);
        unset($data['password_confirmation']);
        if (!empty($data['password'])) $data['password']=bcrypt($data['password']); else unset($data['password']);
        try {
            DB::transaction(function() use($account,$data) { $account->update($data);$this->grantManagement($account); });
        } catch (QueryException $error) {
            if (!$this->isDuplicateStaffEmailException($error)) throw $error;
            return back()->withErrors(['email'=>$this->duplicateStaffEmailMessage()])->withInput($request->except('password','password_confirmation'));
        }
        return redirect()->route('staff.index',['page'=>$this->routePrefix()])->with('message','Akun '.$this->accountRole().' berhasil diubah.');
    }
    public function destroy(Request $request, $id)
    {
        $this->accounts($request)->findOrFail($id)->delete();
        return redirect()->route('staff.index',['page'=>$this->routePrefix()])->with('message','Akun '.$this->accountRole().' dihapus sementara dan tidak dapat login lagi.');
    }
    public function restore(Request $request, $id)
    {
        $this->accounts($request)->onlyTrashed()->findOrFail($id)->restore();
        return redirect()->route('staff.index',['page'=>$this->routePrefix()])->with('message','Akun '.$this->accountRole().' berhasil di-restore.');
    }
    public function forceDelete(Request $request, $id)
    {
        $account=$this->accounts($request)->onlyTrashed()->findOrFail($id);
        try {
            DB::transaction(function() use($account) {
                if (Schema::hasTable('user_application_access')) $account->applicationAccesses()->delete();
                $account->forceDelete();
            });
        } catch (QueryException $error) {
            if ((string)$error->getCode()!=='23000') throw $error;
            return redirect()->route('old-staff',['page'=>$this->routePrefix()])->with('errorr','Akun masih terkait data lain dan tidak dapat dihapus permanen.');
        }
        return redirect()->route('old-staff',['page'=>$this->routePrefix()])->with('message','Akun '.$this->accountRole().' berhasil dihapus permanen.');
    }

}
