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
            'roles'=>$access->rolesForBranch($request->user()->branchStore),'features'=>$access->featuresForBranch($request->user()->branchStore),
            'crudReady'=>$access->crudReady(),'access'=>$access,'ready'=>$access->ready(),
        ]);
    }
    public function update(Request $request, RoleFeatureAccess $access)
    {
        $this->authorizeOwner($request);
        abort_unless($access->crudReady(),503,'Jalankan SQL izin CRUD role terlebih dahulu.');
        $data=$request->validate(['permissions'=>'sometimes|array','permissions.*'=>'array']);
        $submitted=$data['permissions'] ?? [];
        $roles=$access->rolesForBranch($request->user()->branchStore);
        $features=$access->featuresForBranch($request->user()->branchStore);
        $permissions=[];$legacy=[];
        foreach($submitted as $role=>$values) {
            if (!isset($roles[$role])) throw ValidationException::withMessages(['permissions'=>'Role tidak valid.']);
            foreach($values as $feature=>$valuesByAction) {
                if (!isset($features[$feature])) throw ValidationException::withMessages(['permissions'=>'Fitur tidak aktif atau tidak valid.']);
                $actions=RoleFeatureAccess::actionsFor($feature,$role);
                if (!is_array($valuesByAction)) {
                    if (!in_array($valuesByAction,[true,false,0,1,'0','1'],true)) throw ValidationException::withMessages(['permissions'=>'Izin harus berupa nilai boolean.']);
                    $legacy[$role]=true;
                    if ($valuesByAction && !in_array($role,$features[$feature]['roles'],true)) throw ValidationException::withMessages(['permissions'=>'Izin role tidak valid.']);
                    $valuesByAction=array_fill_keys($actions,(bool)$valuesByAction);
                }
                foreach($valuesByAction as $action=>$allowed) {
                    if (!array_key_exists($action,RoleFeatureAccess::actions()) || !in_array($allowed,[true,false,0,1,'0','1'],true)
                        || ($allowed && (!in_array($action,$actions,true) || !in_array($role,$features[$feature]['roles'],true)))) {
                        throw ValidationException::withMessages(['permissions'=>'Aksi atau izin role tidak valid.']);
                    }
                    $permissions[$role][$feature][$action]=(bool)$allowed;
                }
                if (array_filter($permissions[$role][$feature] ?? []) && empty($permissions[$role][$feature]['read'])) {
                    throw ValidationException::withMessages(['permissions'=>'Aktifkan Read sebelum Create, Update, atau Delete.']);
                }
            }
        }
        $branch=(int)$request->user()->branch_store_id;
        DB::transaction(function() use($permissions,$branch,$request,$legacy,$features) {
            foreach(RoleFeatureAccess::roles() as $role=>$label) foreach(RoleFeatureAccess::features() as $feature=>$definition) {
                if (!in_array($role,$definition['roles'],true)) continue;
                $grants=$permissions[$role][$feature] ?? [];
                if (!$grants && !empty($legacy[$role]) && isset($definition['parent'],$features[$feature])) $grants=$permissions[$role][$definition['parent']] ?? [];
                $row=['is_allowed'=>!empty($grants['read']),'updated_by'=>$request->user()->id,'updated_at'=>now()];
                foreach(array_keys(RoleFeatureAccess::actions()) as $action) {
                    $row['can_'.$action]=in_array($action,RoleFeatureAccess::actionsFor($feature,$role),true) && !empty($grants[$action]);
                }
                DB::table('role_feature_permissions')->updateOrInsert([
                    'branch_store_id'=>$branch,'role'=>$role,'feature_key'=>$feature,
                ],$row);
            }
        });
        return redirect()->route('role-feature-access.index')->with('success','Pengaturan akses role berhasil disimpan.');
    }
}
