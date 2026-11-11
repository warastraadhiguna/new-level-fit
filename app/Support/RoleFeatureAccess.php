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
        return [
            'tip_tap_transfer'=>['label'=>'Export Tip-Tap','roles'=>array_keys(self::roles()),'defaults'=>[]],
            'dashboard'=>['label'=>'Dashboard','roles'=>$withPt,'defaults'=>$withPt],
            'leads'=>['label'=>'Lead & Appointment','roles'=>$operations,'defaults'=>$operations],
            'members'=>['label'=>'Data Member','roles'=>$operations,'defaults'=>$operations],
            'membership'=>['label'=>'Membership & Check In','roles'=>$operations,'defaults'=>$operations],
            'pt'=>['label'=>'PT & Check In','roles'=>$withPt,'defaults'=>$withPt],
            'pt_free'=>['label'=>'PT Free','roles'=>$withPt,'defaults'=>$withPt],
            'staff'=>['label'=>'Staff List & Akun Staff','roles'=>['ADMIN'],'defaults'=>['ADMIN']],
            'employees'=>['label'=>'Data Pegawai','roles'=>['ADMIN','HRD','FINANCE'],'defaults'=>['ADMIN','HRD']],
            'enrollment'=>['label'=>'Pendaftaran Wajah','roles'=>['ADMIN','HRD','FINANCE'],'defaults'=>['ADMIN','HRD']],
            'pos_sales'=>['label'=>'POS Kasir & Penjualan','roles'=>['ADMIN','CS','CSPOS'],'defaults'=>['ADMIN','CS','CSPOS']],
            'pos_inventory'=>['label'=>'POS Produk, Pembelian & Stok','roles'=>['ADMIN'],'defaults'=>['ADMIN']],
            'revenue'=>['label'=>'Laporan Revenue','roles'=>array_keys(self::roles()),'defaults'=>array_merge($withPt,['FINANCE'])],
            'reports'=>['label'=>'Laporan Operasional','roles'=>$withPt,'defaults'=>$withPt],
            'packages'=>['label'=>'Pengaturan Paket & Pembayaran','roles'=>['ADMIN'],'defaults'=>['ADMIN']],
            'classes'=>['label'=>'Kelas & Studio','roles'=>$operations,'defaults'=>$operations],
            'other'=>['label'=>'Fitur Operasional Lain','roles'=>$operations,'defaults'=>$operations],
        ];
    }

    public function featuresForBranch(?BranchStore $branch): array
    {
        $features=self::features();
        $switches=[
            'tip_tap_transfer'=>'tip_tap_transfer_enabled',
            'pt_free'=>'pt_free_enabled',
            'pos_sales'=>'pos_inventory_enabled',
            'pos_inventory'=>'pos_inventory_enabled',
            'employees'=>'face_enrollment_enabled',
            'enrollment'=>'face_enrollment_enabled',
        ];
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

    public function allows(User $user, string $feature): bool
    {
        if ($user->isOwner()) return true;
        $definition=self::features()[$feature] ?? null;$role=strtoupper((string)$user->role);
        if (!$definition || !in_array($role,$definition['roles'],true)) return false;
        $matrix=$this->matrix((int)$user->branch_store_id);
        return $matrix[$role][$feature] ?? in_array($role,$definition['defaults'],true);
    }

    public function featureForRoute($route): ?string
    {
        if (!$route) return null;
        $name=$route->getName() ?? '';
        if (Str::is('secret-branch-store.*',$name)) return null;
        if (Str::startsWith($route->uri(),'api/gate/')) return 'membership';
        $patterns=[
            'tip_tap_transfer'=>['tip-tap-transfer.*'],
            'enrollment'=>['face-enrollment.*'], 'employees'=>['employees.*'],
            'staff'=>['staff.*','old-staff','hrd-accounts.*','finance-accounts.*','administrator.*','administrator-*','restore-administrator','customer-service.*','customer-service-*','restore-customer-service','customer-service-pos.*','fitness-consultant.*','fitness-consultant-*','restore-fitness-consultant','personal-trainer.*','personal-trainer-force-delete','restore-personal-trainer','class-instructor.*','class-instructor-*','restore-class-instructor','pt-leader.*','physiotherapy.*','cetak-staff-pdf'],
            'revenue'=>['revenue-report.*'],
            'reports'=>['report-*','*-report','*-report-*','lo-report','ptReportExcel'],
            'pt_free'=>['pt-free.*'],
            'packages'=>['member-package*','dataSoft','restore-member-package-data','trainer-package*','restore-trainer-package-data','payment-method.*','source-code.*','sold-by.*','referral.*','buddy-referral.*','trainer-transaction-type.*'],
            'pos_inventory'=>['pos-products.*','pos-product-categories.*','pos-suppliers.*','pos-purchases.*','pos-stock.*','pos-sales.void'],
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
        return !$feature || $this->allows($user,$feature);
    }
}
