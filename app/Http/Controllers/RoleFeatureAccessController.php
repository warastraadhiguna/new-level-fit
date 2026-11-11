<?php
namespace App\Http\Controllers;
use App\Support\RoleFeatureAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class RoleFeatureAccessController extends Controller
{
    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user() && $request->user()->isOwner(),403);
        abort_unless($request->user()->branch_store_id,403,'Cabang login belum ditentukan.');
    }
    public function index(Request $request, RoleFeatureAccess $access)
    {
        $this->authorizeOwner($request);
        return view('admin.layouts.wrapper',[
            'title'=>'Pengaturan Akses Role','content'=>'admin.role-feature-access.index',
            'roles'=>RoleFeatureAccess::roles(),'features'=>$access->featuresForBranch($request->user()->branchStore),
            'matrix'=>$access->matrix((int)$request->user()->branch_store_id),'ready'=>$access->ready(),
        ]);
    }
    public function update(Request $request, RoleFeatureAccess $access)
    {
        $this->authorizeOwner($request);abort_unless($access->ready(),503,'Jalankan SQL pengaturan akses role terlebih dahulu.');
        $data=$request->validate(['permissions'=>'sometimes|array','permissions.*'=>'array','permissions.*.*'=>'boolean']);
        $permissions=$data['permissions'] ?? [];$roles=RoleFeatureAccess::roles();$features=$access->featuresForBranch($request->user()->branchStore);
        foreach($permissions as $role=>$values) {
            if (!isset($roles[$role])) throw ValidationException::withMessages(['permissions'=>'Role tidak valid.']);
            foreach($values as $feature=>$allowed) {
                if (!isset($features[$feature]) || ($allowed && !in_array($role,$features[$feature]['roles'],true))) {
                    throw ValidationException::withMessages(['permissions'=>'Fitur atau izin role tidak valid.']);
                }
            }
        }
        $branch=(int)$request->user()->branch_store_id;
        DB::transaction(function() use($permissions,$roles,$branch,$request) {
            // One complete matrix per branch; partial saves cannot leave stale grants behind.
            foreach($roles as $role=>$label) foreach(RoleFeatureAccess::features() as $feature=>$definition) {
                if (!in_array($role,$definition['roles'],true)) continue;
                DB::table('role_feature_permissions')->updateOrInsert([
                    'branch_store_id'=>$branch,'role'=>$role,'feature_key'=>$feature,
                ],['is_allowed'=>!empty($permissions[$role][$feature]),'updated_by'=>$request->user()->id,'updated_at'=>now()]);
            }
        });
        return redirect()->route('role-feature-access.index')->with('success','Pengaturan akses role berhasil disimpan.');
    }
}
