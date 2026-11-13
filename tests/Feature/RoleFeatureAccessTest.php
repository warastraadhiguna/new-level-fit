<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class RoleFeatureAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('users',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('branch_store_id');$t->string('full_name');$t->string('email')->unique();
            $t->string('gender');$t->string('role');$t->string('password');$t->rememberToken();$t->timestamps();$t->softDeletes();
        });
        Schema::create('branch_stores',function(Blueprint $t) {
            $t->id();$t->string('name');$t->text('revenue_report_visible_roles')->nullable();
            $t->boolean('tip_tap_transfer_enabled')->default(false);$t->boolean('pos_inventory_enabled')->default(false);$t->boolean('face_enrollment_enabled')->default(false);
            $t->boolean('pt_free_enabled')->default(false);$t->boolean('member_approval_enabled')->default(false);$t->boolean('trainer_approval_enabled')->default(false);
        });
        Schema::create('user_application_access',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('user_id');$t->string('application_code');$t->boolean('is_active');$t->timestamps();$t->unique(['user_id','application_code']);
        });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        foreach (['personal_trainers','class_instructors'] as $table) Schema::create($table,function(Blueprint $t) { $t->id();$t->softDeletes(); });
        require_once database_path('migrations/2026_11_10_000001_create_role_feature_permissions.php');
        (new \CreateRoleFeaturePermissions())->up();
        require_once database_path('migrations/2026_11_13_000003_add_crud_to_role_feature_permissions.php');
        (new \AddCrudToRoleFeaturePermissions())->up();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-10 10:00:00','Asia/Jakarta'));
    }
    private function actor(string $role='FINANCE',int $branch=1): User
    {
        $actor=User::create(['role'=>$role,'branch_store_id'=>$branch,'full_name'=>$role.' User','email'=>uniqid().'@example.com','gender'=>'Male','password'=>bcrypt('SecretPass123')]);
        $actor->applicationAccesses()->create(['application_code'=>'management','is_active'=>true]);
        return $actor;
    }


    private function allow(string $role,string $feature,bool $allowed,int $branch=1): void
    {
        DB::table('role_feature_permissions')->updateOrInsert(['branch_store_id'=>$branch,'role'=>$role,'feature_key'=>$feature],['is_allowed'=>$allowed]);
    }
    public function test_only_owner_can_open_and_save_settings_for_login_branch(): void
    {
        DB::table('branch_stores')->where('id',1)->update(['face_enrollment_enabled'=>true]);
        $this->get('/role-feature-access')->assertRedirect('/login');
        foreach(['ADMIN','CS','CSPOS','FC','PT','HRD','FINANCE'] as $role) {
            $this->actingAs($this->actor($role))->get('/role-feature-access')->assertForbidden();
            $this->put('/role-feature-access',['permissions'=>[]])->assertForbidden();
        }
        $this->assertEquals(0,DB::table('role_feature_permissions')->count());
        $this->actingAs($this->actor('OWNER'))->get('/role-feature-access')->assertOk()->assertSee('Pengaturan Akses Role')->assertSee('permissions[HRD][employees]',false);
        $this->put('/role-feature-access',['branch_store_id'=>2,'permissions'=>['ADMIN'=>['staff'=>1],'HRD'=>['employees'=>1]]])->assertRedirect(route('role-feature-access.index'));
        $this->assertTrue((bool)DB::table('role_feature_permissions')->where('role','HRD')->where('feature_key','employees')->value('is_allowed'));
        $this->assertFalse((bool)DB::table('role_feature_permissions')->where('role','HRD')->where('feature_key','enrollment')->value('is_allowed'));
        $this->assertEquals(0,DB::table('role_feature_permissions')->where('branch_store_id',2)->count());
    }
    public function test_feature_denial_blocks_direct_reads_writes_aliases_and_hides_menu(): void
    {
        $admin=$this->actor('ADMIN');$this->allow('ADMIN','staff',false);
        $this->actingAs($admin);
        foreach(['/staff','/old-staff','/hrd-accounts','/finance-accounts','/administrator/1/edit'] as $url) $this->get($url)->assertForbidden();
        $this->post('/customer-service',[])->assertForbidden();$this->delete('/finance-accounts/1')->assertForbidden();
        // A role home does not require the disabled operational tables to exist.
        $this->actingAs($this->actor('HRD'));$this->allow('HRD','employees',false);$this->allow('HRD','enrollment',false);
        $this->get('/hrd')->assertOk()->assertDontSee('href="'.route('employees.index').'"',false)->assertDontSee('href="'.route('face-enrollment.index').'"',false);
        $this->get('/employees')->assertForbidden();$this->post('/enroll',[])->assertForbidden();
    }
    public function test_another_branch_and_owner_keep_access_and_owner_cannot_be_locked_out(): void
    {
        $this->allow('ADMIN','staff',false);
        $this->actingAs($this->actor('ADMIN',2))->get('/staff?page=hrd')->assertOk();
        $owner=$this->actor('OWNER');$this->allow('OWNER','staff',false);
        $this->actingAs($owner)->get('/staff?page=hrd')->assertOk();
        $this->get('/role-feature-access')->assertOk();
    }
    public function test_settings_validate_roles_features_and_fixed_boundaries_atomically(): void
    {
        $this->actingAs($this->actor('OWNER'));
        foreach([
            ['OWNER'=>['staff'=>1]],['UNKNOWN'=>['staff'=>1]],['ADMIN'=>['unknown'=>1]],
            ['INVALID'=>['staff'=>1]],['FINANCE'=>['pos_inventory'=>1]],['HRD'=>['employees'=>'yes']],
        ] as $permissions) $this->put('/role-feature-access',['permissions'=>$permissions])->assertSessionHasErrors();
        $this->assertEquals(0,DB::table('role_feature_permissions')->count());
    }
    public function test_hrd_can_be_granted_revenue_but_branch_visibility_and_owner_tools_still_apply(): void
    {
        $actor=$this->actor('HRD');$this->allow('HRD','revenue',true);
        DB::table('branch_stores')->where('id',1)->update(['revenue_report_visible_roles'=>json_encode(['OWNER_ONLY'])]);
        $this->actingAs($actor)->get('/revenue-report')->assertForbidden();
        $this->get('/attendance-device')->assertForbidden();
        $this->get('/secret-branch-store')->assertForbidden();
        // Defaults deny grants for account management, even with manually inserted rows.
        $this->allow('HRD','staff',true);$this->get('/staff')->assertForbidden();
    }
    public function test_missing_permissions_table_keeps_defaults_and_cannot_save(): void
    {
        Schema::drop('role_feature_permissions');
        $this->actingAs($this->actor('OWNER'))->get('/role-feature-access')->assertOk()->assertSee('Tabel pengaturan belum tersedia');
        $this->put('/role-feature-access',['permissions'=>[]])->assertStatus(503);
        $this->actingAs($this->actor('ADMIN'))->get('/staff?page=finance')->assertOk();
        $this->actingAs($this->actor('HRD'))->get('/hrd')->assertOk();
    }

    public function test_finance_can_be_given_branch_scoped_employee_access_while_branch_flag_still_applies(): void
    {
        config(['database.connections.face_enrollment'=>config('database.connections.sqlite')]);
        DB::purge('face_enrollment');
        Schema::connection('face_enrollment')->create('employees',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('branch_store_id');$t->string('employee_code')->unique();
            $t->string('full_name');$t->date('born')->nullable();$t->string('position')->nullable();$t->string('phone')->nullable();
            $t->boolean('is_active')->default(true);$t->boolean('all_branch')->default(false);$t->timestamps();$t->softDeletes();
        });
        DB::connection('face_enrollment')->table('employees')->insert([
            ['branch_store_id'=>1,'employee_code'=>'ONE','full_name'=>'Own employee'],
            ['branch_store_id'=>2,'employee_code'=>'TWO','full_name'=>'Foreign employee'],
        ]);
        $finance=$this->actor('FINANCE');$this->allow('FINANCE','employees',true);
        $this->actingAs($finance)->get('/employees')->assertNotFound();
        DB::table('branch_stores')->where('id',1)->update(['face_enrollment_enabled'=>true]);$finance->unsetRelation('branchStore');
        $this->get('/employees')->assertOk()->assertSee('Own employee')->assertDontSee('Foreign employee');
        $this->get('/employees/2/edit')->assertNotFound();
        $this->post('/employees',['employee_code'=>'NEW','full_name'=>'New employee','is_active'=>1,'branch_store_id'=>2])->assertRedirect(route('employees.index'));
        $this->assertEquals(1,DB::connection('face_enrollment')->table('employees')->where('employee_code','NEW')->value('branch_store_id'));
        $this->post('/enroll',[])->assertForbidden();
    }
    public function test_revoked_membership_permission_also_blocks_creation_aliases_and_gate_api(): void
    {
        $cs=$this->actor('CS');$this->allow('CS','membership',false);
        $this->actingAs($cs)->get('/members/1/create-membership')->assertForbidden();
        $this->post('/members/1/store-membership',[])->assertForbidden();
        \Laravel\Sanctum\Sanctum::actingAs($cs);
        $this->getJson('/api/gate/members')->assertForbidden();
        $this->postJson('/api/gate/check-in',[])->assertForbidden();
    }
    public function test_secret_branch_store_is_excluded_from_configurable_permissions(): void
    {
        $this->actingAs($this->actor('OWNER'))->get('/role-feature-access')->assertOk()
            ->assertDontSee('permissions[ADMIN][branch_settings]',false);
        $this->assertArrayNotHasKey('branch_settings',\App\Support\RoleFeatureAccess::features());
        $access=app(\App\Support\RoleFeatureAccess::class);
        $this->assertNull($access->featureForRoute(app('router')->getRoutes()->getByName('secret-branch-store.index')));
        $this->put('/role-feature-access',['permissions'=>['ADMIN'=>['branch_settings'=>1]]])->assertSessionHasErrors();
    }

    public function test_tip_tap_is_off_by_default_and_explicit_grants_allow_page_and_export_for_each_role(): void
    {
        DB::table('branch_stores')->where('id',1)->update(['tip_tap_transfer_enabled'=>true]);
        foreach(array_keys(\App\Support\RoleFeatureAccess::roles()) as $role) {
            $actor=$this->actor($role);$this->actingAs($actor);
            $this->get('/tip-tap-transfer')->assertForbidden();
            $this->post('/tip-tap-transfer/export',[])->assertForbidden();
            $this->allow($role,'tip_tap_transfer',true);
            $this->get('/tip-tap-transfer')->assertOk()->assertSee('Export Data untuk Tip-Tap')->assertSee('nav-text">Export Tip-Tap',false);
            $path=tempnam(sys_get_temp_dir(),'tip-tap-test-');file_put_contents($path,'test export');
            try {
                $this->mock(\App\Services\TipTapTransferExportService::class,function($mock) use($path) {
                    $mock->shouldReceive('create')->once()->andReturn(['path'=>$path,'name'=>'tip-tap-test.zip']);
                });
                $this->post('/tip-tap-transfer/export',['from_at'=>'2026-11-01 00:00:00','until_at'=>'2026-11-09 23:59:59'])
                    ->assertOk()->assertDownload('tip-tap-test.zip');
            } finally { @unlink($path); }
        }
    }
    public function test_tip_tap_grant_can_be_saved_revoked_and_never_bypasses_branch_switch(): void
    {
        DB::table('branch_stores')->where('id',1)->update(['tip_tap_transfer_enabled'=>true]);
        $owner=$this->actor('OWNER');
        $this->actingAs($owner)->put('/role-feature-access',['permissions'=>['HRD'=>['tip_tap_transfer'=>1]]])->assertRedirect(route('role-feature-access.index'));
        DB::table('branch_stores')->where('id',1)->update(['tip_tap_transfer_enabled'=>false]);
        $hrd=$this->actor('HRD');$this->actingAs($hrd)->get('/tip-tap-transfer')->assertNotFound();
        DB::table('branch_stores')->where('id',1)->update(['tip_tap_transfer_enabled'=>true]);$hrd->unsetRelation('branchStore');
        $this->get('/tip-tap-transfer')->assertOk();
        $this->actingAs($this->actor('HRD',2))->get('/tip-tap-transfer')->assertForbidden();
        $this->actingAs($owner)->put('/role-feature-access',['permissions'=>[]])->assertRedirect(route('role-feature-access.index'));
        $this->actingAs($hrd)->get('/tip-tap-transfer')->assertForbidden();
        $this->post('/tip-tap-transfer/export',[])->assertForbidden();
        $this->get('/hrd')->assertOk()->assertDontSee('href="'.route('tip-tap-transfer.index').'"',false);
        $this->get('/role-feature-access')->assertForbidden();
    }
    public function test_disabled_branch_features_are_hidden_and_cannot_be_granted(): void
    {
        // Another branch's enabled features must not affect the login branch.
        DB::table('branch_stores')->where('id',2)->update([
            'tip_tap_transfer_enabled'=>true,'pos_inventory_enabled'=>true,
            'face_enrollment_enabled'=>true,'pt_free_enabled'=>true,
        ]);
        $this->allow('ADMIN','pos_inventory',true);
        $this->actingAs($this->actor('OWNER'));
        $response=$this->get('/role-feature-access')->assertOk()->assertSee('permissions[ADMIN][members]',false);
        foreach(['tip_tap_transfer','pos_sales','pos_inventory','employees','enrollment','pt_free'] as $feature) {
            $response->assertDontSee('permissions[ADMIN]['.$feature.']',false);
            $this->put('/role-feature-access',['permissions'=>['ADMIN'=>[$feature=>1]]])->assertSessionHasErrors();
        }
        // An invalid submission must not change existing permissions.
        $this->assertTrue((bool)DB::table('role_feature_permissions')->where('feature_key','pos_inventory')->value('is_allowed'));
        $this->put('/role-feature-access',['permissions'=>['ADMIN'=>['members'=>1]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse((bool)DB::table('role_feature_permissions')->where('feature_key','pos_inventory')->value('is_allowed'));
    }

    public function test_cspos_settings_are_hidden_and_rejected_when_branch_pos_is_disabled(): void
    {
        DB::table('branch_stores')->where('id',2)->update(['pos_inventory_enabled'=>true]);
        $this->allow('CSPOS','members',true);
        $this->actingAs($this->actor('OWNER'));
        $response=$this->get('/role-feature-access')->assertOk()->assertDontSee('CS POS')
            ->assertDontSee('permissions[CSPOS]',false)->assertSee('permissions[CS][members]',false);
        $this->assertArrayNotHasKey('CSPOS',$response->viewData('roles'));
        foreach([1,0] as $allowed) {
            $this->put('/role-feature-access',['permissions'=>['CSPOS'=>['members'=>$allowed]]])->assertSessionHasErrors();
        }
        $this->assertDatabaseHas('role_feature_permissions',['branch_store_id'=>1,'role'=>'CSPOS','feature_key'=>'members','is_allowed'=>1]);
        $this->put('/role-feature-access',['permissions'=>['CS'=>['members'=>1]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('role_feature_permissions',['branch_store_id'=>1,'role'=>'CSPOS','feature_key'=>'members','is_allowed'=>0]);
        $this->assertDatabaseHas('role_feature_permissions',['branch_store_id'=>1,'role'=>'CS','feature_key'=>'members','is_allowed'=>1]);
    }

    public function test_cspos_settings_can_be_managed_when_login_branch_pos_is_enabled(): void
    {
        DB::table('branch_stores')->where('id',2)->update(['pos_inventory_enabled'=>true]);
        $this->actingAs($this->actor('OWNER',2));
        $this->get('/role-feature-access')->assertOk()->assertSee('CS POS')
            ->assertSee('permissions[CSPOS][members]',false)->assertSee('permissions[CSPOS][pos_sales]',false);
        $this->put('/role-feature-access',['permissions'=>['CSPOS'=>['members'=>1,'pos_sales'=>1]]])
            ->assertSessionHasNoErrors()->assertRedirect();
        foreach(['members','pos_sales'] as $feature) $this->assertDatabaseHas('role_feature_permissions',[
            'branch_store_id'=>2,'role'=>'CSPOS','feature_key'=>$feature,'is_allowed'=>1,
        ]);
        $this->assertEquals(0,DB::table('role_feature_permissions')->where('branch_store_id',1)->count());
    }

    public function test_enabled_branch_features_appear_and_can_be_saved(): void
    {
        DB::table('branch_stores')->where('id',1)->update([
            'tip_tap_transfer_enabled'=>true,'pos_inventory_enabled'=>true,
            'face_enrollment_enabled'=>true,'pt_free_enabled'=>true,
        ]);
        $this->actingAs($this->actor('OWNER'));
        $response=$this->get('/role-feature-access')->assertOk();
        $grants=[];
        foreach(['tip_tap_transfer','pos_sales','pos_inventory','employees','enrollment','pt_free'] as $feature) {
            $response->assertSee('permissions[ADMIN]['.$feature.']',false);
            $grants[$feature]=1;
        }
        $this->put('/role-feature-access',['permissions'=>['ADMIN'=>$grants]])->assertSessionHasNoErrors()->assertRedirect();
        foreach(array_keys($grants) as $feature) $this->assertDatabaseHas('role_feature_permissions',[
            'branch_store_id'=>1,'role'=>'ADMIN','feature_key'=>$feature,'is_allowed'=>1,
        ]);
    }

    public function test_revenue_settings_follow_branch_role_visibility(): void
    {
        DB::table('branch_stores')->where('id',1)->update(['revenue_report_visible_roles'=>json_encode(['ADMIN','FINANCE'])]);
        $owner=$this->actor('OWNER');$this->actingAs($owner);
        $this->get('/role-feature-access')->assertOk()->assertSee('permissions[ADMIN][revenue]',false)
            ->assertSee('permissions[FINANCE][revenue]',false)->assertDontSee('permissions[CS][revenue]',false);
        $this->put('/role-feature-access',['permissions'=>['CS'=>['revenue'=>1]]])->assertSessionHasErrors();
        $this->put('/role-feature-access',['permissions'=>['FINANCE'=>['revenue'=>1]]])->assertSessionHasNoErrors()->assertRedirect();
        DB::table('branch_stores')->where('id',1)->update(['revenue_report_visible_roles'=>json_encode(['OWNER_ONLY'])]);
        $owner->unsetRelation('branchStore');
        $this->get('/role-feature-access')->assertOk()->assertDontSee('permissions[ADMIN][revenue]',false)
            ->assertDontSee('permissions[FINANCE][revenue]',false);
        $this->put('/role-feature-access',['permissions'=>['FINANCE'=>['revenue'=>1]]])->assertSessionHasErrors();
    }

    public function test_read_only_permissions_block_writes_and_legacy_get_mutations(): void
    {
        $owner=$this->actor('OWNER');
        $this->actingAs($owner)->put('/role-feature-access',['permissions'=>['ADMIN'=>[
            'staff'=>['read'=>1],'members'=>['read'=>1],'membership'=>['read'=>1],'pt'=>['read'=>1],
            'membership_checkin'=>['read'=>1],'pt_checkin'=>['read'=>1],
        ]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('role_feature_permissions',['role'=>'ADMIN','feature_key'=>'staff','can_read'=>1,'can_create'=>0,'can_update'=>0,'can_delete'=>0]);
        $this->actingAs($this->actor('ADMIN'))->get('/staff?page=finance')->assertOk();
        foreach(['/administrator/create','/administrator/1/edit','/restore-administrator/1','/reset-check-in/1',
            '/member-check-in/by-registration/1','/pt-check-in/1'] as $url) $this->get($url)->assertForbidden();
        $this->post('/administrator',[])->assertForbidden();
        $this->put('/administrator/1',[])->assertForbidden();
        $this->delete('/administrator/1')->assertForbidden();
        $this->post('/member-check-in',[])->assertForbidden();
        $this->post('/trainer-session-payment',[])->assertForbidden();
    }

    public function test_crud_grants_allow_only_selected_actions_and_stay_branch_scoped(): void
    {
        $this->actingAs($this->actor('OWNER'))->put('/role-feature-access',['permissions'=>['ADMIN'=>[
            'staff'=>['read'=>1,'create'=>1],
        ]]])->assertSessionHasNoErrors()->assertRedirect();
        $payload=['branch_store_id'=>1,'full_name'=>'CRUD Staff','email'=>'crud@example.com','gender'=>'Male','role'=>'ADMIN','password'=>'Secret123','application_access'=>['management']];
        $admin=$this->actor('ADMIN');$this->actingAs($admin)->post('/administrator',$payload)->assertSessionHasNoErrors()->assertRedirect();
        $created=User::where('email','crud@example.com')->firstOrFail();
        $this->put('/administrator/1',[])->assertForbidden();$this->delete('/administrator/1')->assertForbidden();
        $this->assertEquals(0,DB::table('role_feature_permissions')->where('branch_store_id',2)->count());
        $this->actingAs($this->actor('OWNER'))->put('/role-feature-access',['permissions'=>['ADMIN'=>[
            'staff'=>['read'=>1,'update'=>1],
        ]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($admin)->put('/administrator/'.$created->id,array_merge($payload,['full_name'=>'Updated CRUD Staff']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Updated CRUD Staff',$created->fresh()->full_name);
        $this->post('/administrator',[])->assertForbidden();$this->delete('/administrator/1')->assertForbidden();
        $this->actingAs($this->actor('OWNER'))->put('/role-feature-access',['permissions'=>['ADMIN'=>[
            'staff'=>['read'=>1,'delete'=>1],
        ]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($admin)->delete('/administrator/'.$created->id)->assertRedirect();
        $this->assertSoftDeleted('users',['id'=>$created->id]);
        $this->put('/administrator/1',[])->assertForbidden();
    }

    public function test_crud_validation_rejects_writes_without_read_and_unsupported_role_actions_atomically(): void
    {
        $this->actingAs($this->actor('OWNER'));
        foreach([
            ['ADMIN'=>['staff'=>['create'=>1]]],['ADMIN'=>['staff'=>['read'=>1,'unknown'=>1]]],
            ['PT'=>['pt'=>['read'=>1,'unknown'=>1]]],['ADMIN'=>['revenue'=>['read'=>1,'delete'=>1]]],
            ['HRD'=>['staff'=>['create'=>1]]],['ADMIN'=>['staff'=>['read'=>'yes']]],
        ] as $permissions) $this->put('/role-feature-access',['permissions'=>$permissions])->assertSessionHasErrors();
        $this->assertDatabaseCount('role_feature_permissions',0);
    }

    public function test_approvals_are_separate_and_only_configurable_when_enabled_in_branch(): void
    {
        $owner=$this->actor('OWNER');$this->actingAs($owner);
        $this->get('/role-feature-access')->assertOk()->assertDontSee('permissions[ADMIN][membership_approval]',false)
            ->assertDontSee('permissions[ADMIN][pt_approval]',false);
        foreach(['membership_approval','pt_approval'] as $feature) {
            $this->put('/role-feature-access',['permissions'=>['ADMIN'=>[$feature=>['read'=>1]]]])->assertSessionHasErrors();
        }
        DB::table('branch_stores')->where('id',1)->update(['member_approval_enabled'=>true,'trainer_approval_enabled'=>true]);
        $owner->unsetRelation('branchStore');
        $this->get('/role-feature-access')->assertOk()->assertSee('permissions[ADMIN][membership_approval][read]',false)
            ->assertSee('permissions[ADMIN][pt_approval][update]',false)->assertDontSee('permissions[ADMIN][pt_approval][delete]',false);
        $this->put('/role-feature-access',['permissions'=>['ADMIN'=>[
            'membership_approval'=>['read'=>1],'pt_approval'=>['read'=>1],
        ]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->actor('ADMIN'))->put('/member-approval/1',[])->assertForbidden();
        $this->put('/trainer-approval/1',[])->assertForbidden();
    }

    public function test_legacy_grants_survive_crud_migration_and_new_features_inherit_parent_until_saved(): void
    {
        $admin=$this->actor('ADMIN');$this->allow('ADMIN','membership',false);
        $this->allow('ADMIN','staff',true);
        $this->actingAs($admin);
        $access=app(\App\Support\RoleFeatureAccess::class);
        foreach(['create','read','update','delete'] as $action) {
            $this->assertTrue($access->allows($admin,'staff',$action));
            $this->assertFalse($access->allows($admin,'membership_checkin',$action));
        }
        $migration=new \AddCrudToRoleFeaturePermissions;$migration->up();$migration->up();
        $this->assertDatabaseHas('role_feature_permissions',['role'=>'ADMIN','feature_key'=>'staff','is_allowed'=>1,'can_read'=>null]);
    }

    public function test_old_schema_still_reads_legacy_grants_but_cannot_save_crud(): void
    {
        Schema::drop('role_feature_permissions');(new \CreateRoleFeaturePermissions)->up();
        $this->allow('ADMIN','staff',true);
        $this->actingAs($this->actor('OWNER'))->get('/role-feature-access')->assertOk()->assertSee('Kolom izin CRUD belum tersedia');
        $this->put('/role-feature-access',['permissions'=>[]])->assertStatus(503);
        $this->actingAs($this->actor('ADMIN'))->get('/staff?page=finance')->assertOk();
    }

    public function test_denied_controls_match_http_method_and_preserve_read_search_forms(): void
    {
        $admin=$this->actor('ADMIN');
        DB::table('role_feature_permissions')->insert(['branch_store_id'=>1,'role'=>'ADMIN','feature_key'=>'staff',
            'is_allowed'=>true,'can_read'=>true,'can_create'=>false,'can_update'=>false,'can_delete'=>false]);
        $this->actingAs($admin);
        $rules=app(\App\Support\RoleFeatureAccess::class)->deniedControls($admin);
        $blocked=function($path,$method) use($rules) {
            foreach($rules as $rule) if ($rule['method']===$method && preg_match('~'.$rule['path'].'~',$path)) return true;
            return false;
        };
        $this->assertFalse($blocked('/staff','GET'));
        $this->assertTrue($blocked('/administrator','POST'));
        $this->assertTrue($blocked('/administrator/1','DELETE'));
        $this->assertTrue($blocked('/administrator/1/edit','GET'));
        $this->assertTrue($blocked('/restore-administrator/1','GET'));
    }

    public function test_read_only_membership_cannot_mutate_gate_api(): void
    {
        $cs=$this->actor('CS');
        DB::table('role_feature_permissions')->insert(['branch_store_id'=>1,'role'=>'CS','feature_key'=>'membership',
            'is_allowed'=>true,'can_read'=>true,'can_create'=>false,'can_update'=>false,'can_delete'=>false]);
        \Laravel\Sanctum\Sanctum::actingAs($cs);
        $this->postJson('/api/gate/check-in',[])->assertForbidden();
        $this->postJson('/api/gate/check-out',[])->assertForbidden();
    }

    public function test_route_action_catalog_covers_forms_aliases_and_exports(): void
    {
        $access=app(\App\Support\RoleFeatureAccess::class);
        foreach([
            'members.create'=>'create','members.create-membership'=>'create','members.edit'=>'update',
            'restore-administrator'=>'update','resetCheckIn'=>'update','member-check-in.toggle-by-registration-id'=>'create',
            'PTSecondCheckIn'=>'create','members.destroy'=>'delete','pos-sales.void'=>'delete',
            'pos-stock.adjust'=>'update','tip-tap-transfer.export'=>'read','employees.photo'=>'read',
        ] as $name=>$action) $this->assertSame($action,$access->actionForRoute(app('router')->getRoutes()->getByName($name)),$name);
    }

    public function test_all_role_feature_cells_can_be_configured_without_automatically_granting_access(): void
    {
        DB::table('branch_stores')->where('id',1)->update(['pos_inventory_enabled'=>true,'face_enrollment_enabled'=>true]);
        $pt=$this->actor('PT');$this->actingAs($pt);
        $access=app(\App\Support\RoleFeatureAccess::class);
        $this->assertFalse($access->allows($pt,'staff','read'));
        $this->assertFalse($access->allows($pt,'pt','create'));
        $this->actingAs($this->actor('OWNER'))->get('/role-feature-access')->assertOk()
            ->assertSee('permissions[PT][staff][create]',false)
            ->assertSee('permissions[FINANCE][staff][update]',false)
            ->assertSee('permissions[CS][employees][delete]',false)
            ->assertDontSee('>?</span>',false);
    }

    public function test_owner_can_delegate_actual_staff_crud_to_every_other_role_and_revoke_it(): void
    {
        DB::table('branch_stores')->where('id',1)->update(['pos_inventory_enabled'=>true]);
        $owner=$this->actor('OWNER');
        foreach(['CS','CSPOS','FC','PT','HRD','FINANCE'] as $role) {
            $actor=$this->actor($role);
            $this->actingAs($actor)->get('/staff')->assertForbidden();
            $this->actingAs($owner)->put('/role-feature-access',['permissions'=>[$role=>[
                'staff'=>['read'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ]]])->assertSessionHasNoErrors()->assertRedirect();
            $this->actingAs($actor)->get('/staff?page=admin')->assertOk()->assertSee('Administrator')
                ->assertSee('href="'.route('staff.index').'"',false);
            $payload=['branch_store_id'=>1,'full_name'=>'Delegated '.$role,'email'=>'delegated-'.strtolower($role).'@example.com',
                'gender'=>'Male','role'=>'ADMIN','password'=>'Secret123','application_access'=>['management']];
            $this->post('/administrator',$payload)->assertSessionHasNoErrors()->assertRedirect();
            $created=User::where('email',$payload['email'])->firstOrFail();
            $this->put('/administrator/'.$created->id,array_merge($payload,['full_name'=>'Updated '.$role]))->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame('Updated '.$role,$created->fresh()->full_name);
            $this->delete('/administrator/'.$created->id)->assertRedirect();
            $this->assertSoftDeleted('users',['id'=>$created->id]);
            $this->get('/role-feature-access')->assertForbidden();
            $this->get('/secret-branch-store')->assertForbidden();
            $this->get('/attendance-device')->assertForbidden();
            $this->actingAs($owner)->put('/role-feature-access',['permissions'=>[]])->assertSessionHasNoErrors()->assertRedirect();
            $this->actingAs($actor)->get('/staff')->assertForbidden();
        }
    }

    public function test_old_out_of_role_rows_do_not_silently_become_grants_and_pt_writes_need_explicit_crud(): void
    {
        $hrd=$this->actor('HRD');$pt=$this->actor('PT');
        $this->allow('HRD','staff',true);$this->allow('PT','pt',true);
        $this->actingAs($hrd);$access=app(\App\Support\RoleFeatureAccess::class);
        $this->assertFalse($access->allows($hrd,'staff','read'));
        $this->assertTrue($access->allows($pt,'pt','read'));
        $this->assertFalse($access->allows($pt,'pt','create'));
        $this->assertFalse($access->hasExplicitGrant($hrd,'staff','read'));
        $cs=$this->actor('CS');
        DB::table('role_feature_permissions')->insert(['branch_store_id'=>1,'role'=>'CS','feature_key'=>'membership',
            'is_allowed'=>true,'can_read'=>true,'can_create'=>true,'can_update'=>true,'can_delete'=>true]);
        $this->assertFalse($access->allows($cs,'membership_approval','update'));
    }

    public function test_owner_can_configure_cancel_lo_as_a_separate_update_permission(): void
    {
        $this->actingAs($this->actor('OWNER'))->get('/role-feature-access')->assertOk()
            ->assertSee('Cancel LO')->assertSee('permissions[CS][lo_cancel][update]',false)
            ->assertDontSee('permissions[CS][lo_cancel][delete]',false);
        $this->put('/role-feature-access',['permissions'=>['CS'=>['members'=>['read'=>1],'lo_cancel'=>['read'=>1,'update'=>1]]]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('role_feature_permissions',['branch_store_id'=>1,'role'=>'CS','feature_key'=>'lo_cancel','can_read'=>1,'can_update'=>1,'can_delete'=>0]);
        $access=app(\App\Support\RoleFeatureAccess::class);
        $route=app('router')->getRoutes()->getByName('cancelLayoutOrientation');
        $this->assertSame('lo_cancel',$access->featureForRoute($route));
        $this->assertSame('update',$access->actionForRoute($route));
    }

    public function test_migration_is_repeatable_and_route_catalog_covers_all_protected_endpoints(): void
    {
        (new \CreateRoleFeaturePermissions())->up();
        $access=app(\App\Support\RoleFeatureAccess::class);
        foreach(app('router')->getRoutes() as $route) {
            if (!in_array('admin',$route->middleware(),true)) continue;
            $name=$route->getName() ?? '';
            if (in_array($name,['hrd.home','finance.home'],true) || \Illuminate\Support\Str::is(['attendance-device.*','role-feature-access.*','secret-branch-store.*'],$name)) continue;
            $this->assertArrayHasKey($access->featureForRoute($route),\App\Support\RoleFeatureAccess::features(),$route->uri());
        }
        foreach(['member-package.index'=>'packages','trainer-package.index'=>'packages','revenue-report.index'=>'revenue','report-member-pt-checkin'=>'reports','members.store-membership'=>'membership','trainer-session.move-branch'=>'pt','pos-sales.void'=>'pos_inventory','tip-tap-transfer.index'=>'tip_tap_transfer','tip-tap-transfer.export'=>'tip_tap_transfer'] as $name=>$feature) {
            $this->assertEquals($feature,$access->featureForRoute(app('router')->getRoutes()->getByName($name)));
        }
    }
}
