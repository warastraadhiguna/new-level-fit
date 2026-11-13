<?php

namespace Tests\Feature;

use App\Models\{User,BranchStore};
use App\Models\Member\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema,Log};
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class CancelLayoutOrientationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('branch_stores',function(Blueprint $t) { $t->id();$t->string('name'); });
        Schema::create('users',function(Blueprint $t) {
            $t->id();$t->integer('branch_store_id');$t->string('full_name');$t->string('role');$t->string('email')->unique();
            $t->string('password');$t->string('gender');$t->timestamps();$t->softDeletes();
        });
        Schema::create('user_application_access',function(Blueprint $t) {
            $t->id();$t->integer('user_id');$t->string('application_code');$t->boolean('is_active');$t->timestamps();
        });
        Schema::create('members',function(Blueprint $t) {
            $t->id();$t->integer('branch_store_id');$t->string('full_name');$t->string('member_code');
            $t->boolean('lo_is_used')->default(false);$t->dateTime('lo_start_date')->nullable();
            $t->dateTime('lo_end')->nullable();$t->integer('lo_pt_by')->nullable();$t->integer('lo_days')->default(30);
            $t->string('status')->default('sell');$t->string('description')->nullable();
        });
        Schema::create('personal_trainers',function(Blueprint $t) { $t->id();$t->string('full_name'); });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        DB::table('personal_trainers')->insert([['id'=>1,'full_name'=>'First PT'],['id'=>2,'full_name'=>'Correct PT']]);
        DB::table('members')->insert([
            ['id'=>1,'branch_store_id'=>1,'full_name'=>'First Member','member_code'=>'M1','lo_is_used'=>true,'lo_start_date'=>'2026-11-13 09:00:00','lo_end'=>null,'lo_pt_by'=>1,'description'=>'Keep this'],
            ['id'=>2,'branch_store_id'=>1,'full_name'=>'Second Member','member_code'=>'M2','lo_is_used'=>true,'lo_start_date'=>'2026-11-13 09:00:00','lo_end'=>'2026-11-13 10:00:00','lo_pt_by'=>1,'description'=>'Keep this'],
            ['id'=>3,'branch_store_id'=>2,'full_name'=>'Foreign Member','member_code'=>'M3','lo_is_used'=>true,'lo_start_date'=>'2026-11-13 09:00:00','lo_end'=>null,'lo_pt_by'=>1,'description'=>'Keep this'],
        ]);
        require_once database_path('migrations/2026_11_10_000001_create_role_feature_permissions.php');(new \CreateRoleFeaturePermissions)->up();
        require_once database_path('migrations/2026_11_13_000003_add_crud_to_role_feature_permissions.php');(new \AddCrudToRoleFeaturePermissions)->up();
    }

    private function actor(string $role='OWNER',int $branch=1): User
    {
        $user=User::create(['role'=>$role,'branch_store_id'=>$branch,'full_name'=>'Operator','email'=>uniqid().'@example.com','password'=>'test','gender'=>'Male']);
        $user->applicationAccesses()->create(['application_code'=>'management','is_active'=>true]);
        return $user;
    }

    private function grant(string $role,bool $update): void
    {
        DB::table('role_feature_permissions')->updateOrInsert(['branch_store_id'=>1,'role'=>$role,'feature_key'=>'lo_cancel'],[
            'is_allowed'=>true,'can_read'=>true,'can_update'=>$update,'can_create'=>false,'can_delete'=>false,
        ]);
    }

    public function test_owner_can_cancel_running_and_completed_lo_and_restart_with_correct_pt(): void
    {
        Log::spy();$owner=$this->actor();$this->actingAs($owner);
        foreach([1,2] as $id) {
            $this->put(route('cancelLayoutOrientation',$id),['search'=>'First','per_page'=>25])->assertRedirect(route('members.index',['search'=>'First','per_page'=>25]));
            $this->assertDatabaseHas('members',['id'=>$id,'lo_is_used'=>0,'lo_start_date'=>null,'lo_end'=>null,'lo_pt_by'=>null,'lo_days'=>30,'description'=>'Keep this']);
        }
        $this->assertDatabaseHas('members',['id'=>3,'lo_is_used'=>1]);
        Log::shouldHaveReceived('info')->twice();
        $this->put(route('prosesLayoutOrientation',1),['lo_pt_by'=>2])->assertSessionHasNoErrors()->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('members',['id'=>1,'lo_is_used'=>1,'lo_pt_by'=>2,'lo_end'=>null]);
    }

    public function test_cancel_is_denied_by_default_and_only_explicit_update_permission_allows_it(): void
    {
        $this->put(route('cancelLayoutOrientation',1))->assertRedirect(route('login'));
        foreach(['ADMIN','CS','CSPOS','FC','PT','HRD','FINANCE'] as $role) {
            $user=$this->actor($role);$this->actingAs($user)->put(route('cancelLayoutOrientation',1))->assertForbidden();
            $this->grant($role,false);
            $this->put(route('cancelLayoutOrientation',1))->assertForbidden();
        }
        $this->assertDatabaseHas('members',['id'=>1,'lo_is_used'=>true]);
        $this->grant('CS',true);$this->actingAs($this->actor('CS'))->put(route('cancelLayoutOrientation',1))->assertRedirect();
        $this->assertDatabaseHas('members',['id'=>1,'lo_is_used'=>false]);
    }

    public function test_cancel_start_and_stop_cannot_change_another_branch_member(): void
    {
        $this->actingAs($this->actor());
        $this->put(route('cancelLayoutOrientation',3))->assertNotFound();
        $this->put(route('prosesLayoutOrientation',3),['lo_pt_by'=>2])->assertNotFound();
        $this->post(route('stopLayoutOrientation',3))->assertNotFound();
        $this->assertDatabaseHas('members',['id'=>3,'lo_is_used'=>true,'lo_pt_by'=>1]);
    }

    public function test_stale_start_stop_or_cancel_cannot_overwrite_lo_state(): void
    {
        $this->actingAs($this->actor());
        $this->putJson(route('prosesLayoutOrientation',1),['lo_pt_by'=>2])->assertUnprocessable();
        $this->assertDatabaseHas('members',['id'=>1,'lo_is_used'=>true,'lo_pt_by'=>1]);
        $this->put(route('cancelLayoutOrientation',1))->assertRedirect();
        $this->putJson(route('cancelLayoutOrientation',1))->assertUnprocessable();
        $this->postJson(route('stopLayoutOrientation',1))->assertUnprocessable();
        $this->assertDatabaseHas('members',['id'=>1,'lo_is_used'=>false,'lo_end'=>null]);
    }

    public function test_cancel_button_is_only_visible_for_used_lo_and_roles_with_permission(): void
    {
        $member=Member::findOrFail(1);
        $data=['members'=>new LengthAwarePaginator(collect([$member]),1,10),'search'=>'','perPage'=>10,'sort'=>'created_at','direction'=>'desc'];
        $cs=$this->actor('CS');$this->actingAs($cs);
        $this->view('admin.members.index',$data)->assertDontSee('Cancel LO');
        $this->grant('CS',true);
        // A new request gives the access service its fresh permission cache.
        $this->app->instance('request',\Illuminate\Http\Request::create('/members'));
        $this->view('admin.members.index',$data)->assertSee('Cancel LO')->assertSee('action="'.route('cancelLayoutOrientation',1).'"',false);
        $member->lo_is_used=false;
        $this->view('admin.members.index',$data)->assertDontSee('Cancel LO');
    }
}
