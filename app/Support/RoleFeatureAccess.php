<?php
namespace App\Support;

use App\Models\{User,BranchStore};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;

class RoleFeatureAccess
{
    public static function roles(): array
    {
        return ['ADMIN'=>'Administrator','CS'=>'Customer Service','CSPOS'=>'CS POS','FC'=>'Fitness Consultant','PT'=>'PT User','HRD'=>'HRD','FINANCE'=>'Finance'];
    }

    public static function features(): array
    {
        $operations=['ADMIN','CS','CSPOS','FC'];
        $withPt=array_merge($operations,['PT']);
        $features=[
            'tip_tap_transfer'=>['label'=>'Export Tip-Tap','roles'=>array_keys(self::roles()),'defaults'=>[]],
            'dashboard'=>['label'=>'Dashboard','roles'=>$withPt,'defaults'=>$withPt],
            'leads'=>['label'=>'Lead & Appointment','roles'=>$operations,'defaults'=>$operations],
            'members'=>['label'=>'Data Member','roles'=>$operations,'defaults'=>$operations],
            'lo_cancel'=>['label'=>'Cancel LO','roles'=>$operations,'defaults'=>[]],
            'membership'=>['label'=>'Membership','roles'=>$operations,'defaults'=>$operations],
            'pt'=>['label'=>'PT','roles'=>$withPt,'defaults'=>$withPt],
            'membership_approval'=>['label'=>'Approval Membership','roles'=>['ADMIN'],'defaults'=>['ADMIN'],'parent'=>'membership'],
            'pt_approval'=>['label'=>'Approval PT','roles'=>['ADMIN'],'defaults'=>['ADMIN'],'parent'=>'pt'],
            'membership_checkin'=>['label'=>'Check-in / Check-out Membership','roles'=>$operations,'defaults'=>$operations,'parent'=>'membership'],
            'pt_checkin'=>['label'=>'Check-in / Check-out PT','roles'=>$operations,'defaults'=>$operations,'parent'=>'pt'],
            'membership_payments'=>['label'=>'Pembayaran Membership','roles'=>$operations,'defaults'=>$operations,'parent'=>'membership'],
            'pt_payments'=>['label'=>'Pembayaran PT','roles'=>$operations,'defaults'=>$operations,'parent'=>'pt'],
            'pt_free'=>['label'=>'PT Free','roles'=>$withPt,'defaults'=>$withPt],
            'staff'=>['label'=>'Staff List & Akun Staff','roles'=>['ADMIN'],'defaults'=>['ADMIN']],
            'employees'=>['label'=>'Data Pegawai','roles'=>['ADMIN','HRD','FINANCE'],'defaults'=>['ADMIN','HRD']],
            'attendance_shifts'=>['label'=>'Template Shift Pegawai','roles'=>['ADMIN','HRD'],'defaults'=>['ADMIN','HRD']],
            'attendance_schedules'=>['label'=>'Jadwal Shift Pegawai','roles'=>['ADMIN','HRD'],'defaults'=>['ADMIN','HRD']],
            'attendance_leaves'=>['label'=>'Izin & Cuti Pegawai (Update: persetujuan)','roles'=>['ADMIN','HRD'],'defaults'=>['ADMIN','HRD']],
            'attendance_reports'=>['label'=>'Rekap Kehadiran Pegawai','roles'=>['ADMIN','HRD'],'defaults'=>['ADMIN','HRD']],
            'enrollment'=>['label'=>'Pendaftaran Wajah','roles'=>['ADMIN','HRD','FINANCE'],'defaults'=>['ADMIN','HRD']],
            'pos_sales'=>['label'=>'POS Kasir & Penjualan','roles'=>['ADMIN','CS','CSPOS'],'defaults'=>['ADMIN','CS','CSPOS']],
            'pos_inventory'=>['label'=>'POS Produk, Pembelian & Stok','roles'=>['ADMIN'],'defaults'=>['ADMIN']],
            'revenue'=>['label'=>'Laporan Revenue','roles'=>array_keys(self::roles()),'defaults'=>array_merge($withPt,['FINANCE'])],
            'reports'=>['label'=>'Laporan Operasional','roles'=>$withPt,'defaults'=>$withPt],
            'packages'=>['label'=>'Pengaturan Paket & Pembayaran','roles'=>['ADMIN'],'defaults'=>['ADMIN']],
            'classes'=>['label'=>'Kelas & Studio','roles'=>$operations,'defaults'=>$operations],
            'other'=>['label'=>'Fitur Operasional Lain','roles'=>$operations,'defaults'=>$operations],
        ];
        foreach($features as &$feature) {
            $feature['native_roles']=$feature['roles'];
            $feature['roles']=array_keys(self::roles());
        }
        unset($feature);
        return $features;
    }

    public function rolesForBranch(?BranchStore $branch): array
    {
        $roles=self::roles();
        if (!$branch || !$branch->pos_inventory_enabled) unset($roles['CSPOS']);
        return $roles;
    }

    private static function branchFlags(): array
    {
        return [
            'membership_approval'=>'member_approval_enabled',
            'pt_approval'=>'trainer_approval_enabled',
            'tip_tap_transfer'=>'tip_tap_transfer_enabled',
            'pt_free'=>'pt_free_enabled',
            'pos_sales'=>'pos_inventory_enabled',
            'pos_inventory'=>'pos_inventory_enabled',
            'employees'=>'face_enrollment_enabled',
            'enrollment'=>'face_enrollment_enabled',
            'attendance_shifts'=>'face_enrollment_enabled',
            'attendance_schedules'=>'face_enrollment_enabled',
            'attendance_leaves'=>'face_enrollment_enabled',
            'attendance_reports'=>'face_enrollment_enabled',

        ];
    }

    public function featuresForBranch(?BranchStore $branch): array
    {
        $features=self::features();
        $switches=self::branchFlags();
        foreach($switches as $feature=>$flag) {
            if (!$branch || !$branch->$flag) unset($features[$feature]);
        }
        $features['revenue']['roles']=array_values(array_filter($features['revenue']['roles'],function($role) use($branch) {
            return $branch && $branch->canRoleViewRevenueReport($role);
        }));
        if (!$features['revenue']['roles']) unset($features['revenue']);
        return $features;
    }

    public function ready(): bool
    {
        $request=request();
        if (!$request->attributes->has('role_features_ready')) {
            $request->attributes->set('role_features_ready',Schema::hasTable('role_feature_permissions'));
        }
        return $request->attributes->get('role_features_ready');
    }

    public function matrix(int $branch): array
    {
        $key='role_features_'.$branch;
        if (!request()->attributes->has($key)) {
            $matrix=[];
            if ($this->ready()) foreach(DB::table('role_feature_permissions')->where('branch_store_id',$branch)->get() as $row) {
                $matrix[$row->role][$row->feature_key]=(bool)$row->is_allowed;
            }
            request()->attributes->set($key,$matrix);
        }
        return request()->attributes->get($key);
    }

    public static function actions(): array
    {
        return ['create'=>'Create','read'=>'Read','update'=>'Update','delete'=>'Delete'];
    }

    public static function actionsFor(string $feature, string $role): array
    {
        $definition=self::features()[$feature] ?? null;
        if (!$definition || !in_array($role,$definition['roles'],true)) return [];
        if (in_array($feature,['dashboard','revenue','reports','tip_tap_transfer'],true)) return ['read'];
        if (in_array($feature,['membership_approval','pt_approval','lo_cancel'],true)) return ['read','update'];
        if ($feature==='attendance_reports') return ['read'];
        if ($feature==='attendance_schedules') return ['create','read','delete'];
        if ($feature==='attendance_leaves') return ['create','read','update'];
        if ($feature==='pos_sales') return ['create','read'];
        if (in_array($feature,['enrollment','membership_checkin','pt_checkin','membership_payments','pt_payments'],true)) return ['create','read','delete'];
        return array_keys(self::actions());
    }

    public function crudReady(): bool
    {
        if (!$this->ready()) return false;
        $request=request();
        if (!$request->attributes->has('role_features_crud_ready')) {
            $ready=true;
            foreach(array_keys(self::actions()) as $action) $ready=$ready && Schema::hasColumn('role_feature_permissions','can_'.$action);
            $request->attributes->set('role_features_crud_ready',$ready);
        }
        return $request->attributes->get('role_features_crud_ready');
    }

    public function actionMatrix(int $branch): array
    {
        $key='role_feature_actions_'.$branch;
        if (!request()->attributes->has($key)) {
            $matrix=[];
            if ($this->ready()) foreach(DB::table('role_feature_permissions')->where('branch_store_id',$branch)->get() as $row) {
                foreach(array_keys(self::actions()) as $action) {
                    $matrix[$row->role][$row->feature_key][$action]=(bool)($row->{'can_'.$action} ?? $row->is_allowed);
                    $matrix[$row->role][$row->feature_key]['explicit'][$action]=isset($row->{'can_'.$action});
                }
            }
            request()->attributes->set($key,$matrix);
        }
        return request()->attributes->get($key);
    }

    public function allows(User $user, string $feature, string $action='read'): bool
    {
        if ($user->isOwner()) return true;
        if (!$user->branch_store_id) return false;
        $role=strtoupper((string)$user->role);
        if (!in_array($action,self::actionsFor($feature,$role),true)) return false;
        $definition=self::features()[$feature];
        $matrix=$this->actionMatrix((int)$user->branch_store_id);
        $permission=$matrix[$role][$feature] ?? null;
        if ($permission===null && isset($definition['parent']) && in_array($role,$definition['native_roles'],true)) return $this->allows($user,$definition['parent'],$action);
        if ($permission===null) return in_array($role,$definition['defaults'],true) && ($role!=='PT' || $action==='read');
        if (!in_array($role,$definition['native_roles'],true) || ($role==='PT' && $action!=='read')) {
            if (empty($permission['explicit']['read']) || empty($permission['explicit'][$action])) return false;
        }
        return $permission['read'] && $permission[$action];
    }

    public function hasExplicitGrant(User $user, string $feature, string $action='read'): bool
    {
        if (!$user->branch_store_id) return false;
        $permission=$this->actionMatrix((int)$user->branch_store_id)[strtoupper((string)$user->role)][$feature] ?? [];
        return !empty($permission['explicit']['read']) && !empty($permission['explicit'][$action])
            && $this->allows($user,$feature,$action) && $this->branchEnabled($user,$feature);
    }

    public function hasExplicitRouteGrant(User $user, $route=null): bool
    {
        $route=$route ?? request()->route();
        $feature=$this->featureForRoute($route);
        return $feature && $this->hasExplicitGrant($user,$feature,$this->actionForRoute($route,request()->method()));
    }

    public function expandedNavigation(User $user): bool
    {
        $role=strtoupper((string)$user->role);
        foreach(self::features() as $feature=>$definition) {
            if (!in_array($role,$definition['native_roles'],true) && $this->hasExplicitGrant($user,$feature)) return true;
            if ($role==='PT') foreach(['create','update','delete'] as $action) if ($this->hasExplicitGrant($user,$feature,$action)) return true;
        }
        return false;
    }

    public function branchEnabled(User $user, string $feature): bool
    {
        $flags=self::branchFlags();
        return !isset($flags[$feature]) || (bool)optional($user->branchStore)->{$flags[$feature]};
    }

    public function actionForRoute($route, ?string $method=null): string
    {
        if (!$route) return 'read';
        $name=$route->getName() ?? '';
        $method=$method ?? $route->methods()[0];
        $controllerAction=$route->getActionMethod();
        if ($name==='tip-tap-transfer.export') return 'read';
        // Legacy mutation URLs must never inherit read access merely because they use GET.
        if (in_array($name,['member-check-in.toggle-by-registration-id','member-check-in.toggle-by-card-number','PTSecondCheckIn','LGTCheckIn','LGTSecondCheckIn','pt-free.check-in.session','pt-free.check-in'],true)) return 'create';
        if (Str::is(['restore-*','*.restore'],$name) || in_array($controllerAction,['restore','resetCheckIn'],true)) return 'update';
        if (in_array($controllerAction,['forceDelete','destroy'],true) || $name==='pos-sales.void') return 'delete';
        if ($controllerAction==='create' || Str::is(['*.create','*.create-membership'],$name) || in_array($name,['renewal','oneDayVisit','one-day-visit-lead','useLayoutOrientation'],true)) return 'create';
        if ($controllerAction==='edit' || Str::is(['*.edit','*.move-branch-page'],$name) || $name==='edit-member-sell') return 'update';
        if (in_array($method,['PUT','PATCH'],true)) return 'update';
        if ($method==='DELETE') return 'delete';
        if ($method==='POST') {
            if (in_array($name,['stopLayoutOrientation','pos-purchases.receive','pos-stock.adjust'],true) || Str::contains($name,['update','unfreeze'])) return 'update';
            return 'create';
        }
        return 'read';
    }

    public function featureForRoute($route): ?string
    {
        if (!$route) return null;
        $name=$route->getName() ?? '';
        if (Str::is('secret-branch-store.*',$name)) return null;
        if (Str::startsWith($route->uri(),'api/gate/')) return in_array('POST',$route->methods(),true) ? 'membership_checkin' : 'membership';
        $patterns=[
            'attendance_shifts'=>['employee-shifts.*'], 'attendance_schedules'=>['employee-schedules.*'],
            'attendance_leaves'=>['employee-leaves.*'], 'attendance_reports'=>['employee-attendance.*'],
            'lo_cancel'=>['cancelLayoutOrientation'],
            'membership_approval'=>['member-approval.*'], 'pt_approval'=>['trainer-approval.*'],
            'membership_payments'=>['member-registration-payment.*','member-payment.*'],
            'pt_payments'=>['trainer-session-payment.*'],
            'membership_checkin'=>['member-check-in.*'],
            'pt_checkin'=>['trainer-session-check-in.*','PTSecondCheckIn','LGTCheckIn','LGTSecondCheckIn'],
            'tip_tap_transfer'=>['tip-tap-transfer.*'],
            'enrollment'=>['face-enrollment.*'], 'employees'=>['employees.*'],
            'staff'=>['staff.*','old-staff','hrd-accounts.*','finance-accounts.*','administrator.*','administrator-*','restore-administrator','customer-service.*','customer-service-*','restore-customer-service','customer-service-pos.*','fitness-consultant.*','fitness-consultant-*','restore-fitness-consultant','personal-trainer.*','personal-trainer-force-delete','restore-personal-trainer','class-instructor.*','class-instructor-*','restore-class-instructor','pt-leader.*','physiotherapy.*','cetak-staff-pdf'],
            'revenue'=>['revenue-report.*'],
            'reports'=>['report-*','*-report','*-report-*','lo-report','ptReportExcel'],
            'pt_free'=>['pt-free.*'],
            'packages'=>['member-package*','dataSoft','restore-member-package-data','trainer-package*','restore-trainer-package-data','payment-method.*','source-code.*','sold-by.*','referral.*','buddy-referral.*','trainer-transaction-type.*'],
            'pos_inventory'=>['pos-products.*','pos-products.attach','pos-product-categories.*','pos-suppliers.*','pos-purchases.*','pos-stock.*','pos-sales.void'],
            'pos_sales'=>['pos.*','pos-sales.*'],
            'classes'=>['class.*','class-session.*','class-schedule.*','class-detail.*','studio.*'],
            'leads'=>['add-data','one-day-visit-lead','openMembers','leads.*','missed-guest.*','appointment','appointmentSchedule','storeAppointment'],
            'membership_actions'=>['members.create-membership','members.store-membership','members.membership-history','members.membership-check-in-history'],
            'pt_actions'=>['members.pt-history','members.pt-check-in-history'],
            'members'=>['members.*','member.*','edit-member-sell','update-member-sell','all-member','member-filter','member-report','resetCheckIn'],
            'pt'=>['running-session.*','cutiTrainerSession','cutiLGT','cetak-trainer-session-pdf','print-trainer-session-detail-pdf','personal-trainer-list.*','all-personal-trainer','trainer-*','pt-*','PTSecondCheckIn','LGTCheckIn','LGTSecondCheckIn','lgt','payment-receipts.trainer','detail-pt-history','checkMemberExistence'],
            'membership'=>['transfer-package.*','print-member-registration-over-pdf','all-member-expired','member-*','memberRegistrationExcel','mmember-*','renewMemberRegistration','renewal','cuti','stopLeaveDays','stopLayoutOrientation','prosesLayoutOrientation','useLayoutOrientation','oneDayVisit','history-member-registration','detail-history-member-registration','membership-agreement','print-member-card.*','payment-receipts.member','renewMemberRegistration'],
            'dashboard'=>['dashboard'],
        ];
        foreach($patterns as $feature=>$matches) if (Str::is($matches,$name)) return ['membership_actions'=>'membership','pt_actions'=>'pt'][$feature] ?? $feature;
        // Login, logout, role home pages, and fixed Owner tools are not configurable.
        if (in_array($name,['login','logout','hrd.home','finance.home'],true) || Str::is(['attendance-device.*','role-feature-access.*'],$name)) return null;
        if ($route->uri()==='filter-data') return 'membership';
        return in_array('admin',$route->middleware(),true) ? 'other' : null;
    }

    public function allowsRoute(User $user, string $name): bool
    {
        $feature=$this->featureForRoute(app('router')->getRoutes()->getByName($name));
        return !$feature || ($this->branchEnabled($user,$feature) && $this->allows($user,$feature,$this->actionForRoute(app('router')->getRoutes()->getByName($name))));
    }

    public function deniedControls(User $user): array
    {
        if ($user->isOwner()) return [];
        $rules=[];
        foreach(app('router')->getRoutes() as $route) {
            $feature=$this->featureForRoute($route);
            if (!$feature) continue;
            $pattern='';
            foreach(explode('/',$route->uri()) as $segment) {
                if (preg_match('/^\{[^}]+\?\}$/',$segment)) $pattern.='(?:/[^/]+)?';
                elseif (preg_match('/^\{[^}]+\}$/',$segment)) $pattern.='/[^/]+';
                else $pattern.='/'.preg_quote($segment,'~');
            }
            foreach($route->methods() as $method) {
                if ($method==='HEAD') continue;
                if (!$this->branchEnabled($user,$feature) || !$this->allows($user,$feature,$this->actionForRoute($route,$method))) {
                    $rules[]=['path'=>'^'.$pattern.'/?$','method'=>$method];
                }
            }
        }
        return $rules;
    }
}
