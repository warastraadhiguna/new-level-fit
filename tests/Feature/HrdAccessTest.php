<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema,Hash,Http,Auth};
use Tests\TestCase;
class HrdAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'face_enrollment','database.connections.face_enrollment'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);DB::purge('face_enrollment');
        Schema::create('users',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('branch_store_id');$t->string('full_name');$t->string('email')->unique();
            $t->string('gender');$t->string('role');$t->string('password');$t->rememberToken();$t->timestamps();$t->softDeletes();
        });
        Schema::create('branch_stores',function(Blueprint $t) { $t->id();$t->string('name');$t->boolean('face_enrollment_enabled')->default(true); });
        Schema::create('user_application_access',function(Blueprint $t) {
            $t->id();$t->unsignedInteger('user_id');$t->string('application_code');$t->boolean('is_active');$t->timestamps();$t->unique(['user_id','application_code']);
        });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        require_once database_path('migrations/2026_11_04_000001_create_independent_employees.php');(new \CreateIndependentEmployees)->up();
        require_once database_path('migrations/2026_11_04_000002_create_face_attendance_samples.php');(new \CreateFaceAttendanceSamples)->up();
        require_once database_path('migrations/2026_11_05_000001_add_born_to_employees.php');(new \AddBornToEmployees)->up();
    }
    private function actor(string $role='HRD',int $branch=1): User
    {
        $actor=User::create(['role'=>$role,'branch_store_id'=>$branch,'full_name'=>$role.' User','email'=>uniqid().'@example.com','gender'=>'Male','password'=>bcrypt('SecretPass123')]);
        $actor->applicationAccesses()->create(['application_code'=>'management','is_active'=>true]);
        return $actor;
    }
    private function payload(): array
    {
        return ['full_name'=>'New HRD','email'=>'New.Hrd@example.com','gender'=>'Female','password'=>'SecretPass123','password_confirmation'=>'SecretPass123'];
    }
    public function test_hrd_can_login_and_is_sent_to_own_home_without_admin_privileges(): void
    {
        $actor=$this->actor();
        $this->assertFalse($actor->isAdmin());$this->assertTrue($actor->isHrd());
        $this->post('/login',['email'=>$actor->email,'password'=>'SecretPass123'])->assertRedirect(route('hrd.home'));
        $this->assertAuthenticatedAs($actor);
        $this->get('/')->assertRedirect(route('hrd.home'));
        $this->get('/hrd')->assertOk()->assertSee('Data Pegawai')->assertSee('Pendaftaran Wajah')->assertDontSee('Perangkat Absensi')->assertDontSee('Member Active');
        $this->post('/logout')->assertRedirect('/');$this->assertGuest();
    }
    public function test_hrd_manages_only_own_branch_employees_and_face_samples(): void
    {
        $this->actingAs($this->actor());
        $payload=['employee_code'=>'E1','full_name'=>'Own Staff','is_active'=>1,'born'=>'1990-01-01','branch_store_id'=>2];
        $this->post('/employees',$payload)->assertRedirect(route('employees.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employees',['employee_code'=>'E1','branch_store_id'=>1]);
        $id=DB::table('employees')->value('id');
        DB::table('employees')->insert(['employee_code'=>'E2','full_name'=>'Other Staff','branch_store_id'=>2]);
        $this->get('/employees')->assertOk()->assertSee('Own Staff')->assertDontSee('Other Staff');
        $this->get('/enroll')->assertOk()->assertSee('Own Staff')->assertDontSee('Other Staff');
        $this->get('/employees/2/edit')->assertNotFound();$this->delete('/employees/2')->assertNotFound();
        Http::fake(['*'=>Http::response(['success'=>true,'quality_checked'=>true,'face_count'=>1,'embedding'=>array_fill(0,128,0.1),'model'=>config('face.model')])]);
        $body=['employee_id'=>$id,'photo'=>'data:image/jpeg;base64,'.base64_encode(file_get_contents(base_path('tests/Fixtures/camera.jpg')))];
        $this->postJson('/enroll',$body)->assertOk();
        $this->getJson('/enroll/samples?employee_id='.$id)->assertOk()->assertJsonPath('sample_count',1);
        $sample=DB::table('face_attendance_samples')->value('id');
        $this->deleteJson('/enroll/samples/'.$sample,['employee_id'=>$id])->assertOk();
        $this->getJson('/enroll/samples?employee_id=2')->assertNotFound();
    }
    public function test_hrd_cannot_open_other_management_routes_or_change_own_role_or_branch(): void
    {
        $this->actingAs($this->actor());
        foreach(['/member','/staff','/secret-branch-store','/attendance-device','/hrd-accounts','/tip-tap-transfer'] as $url) $this->get($url)->assertForbidden();
        $this->post('/attendance-device/pair',['label'=>'HP'])->assertForbidden();
        $this->post('/attendance-device/revoke',['device_id'=>1])->assertForbidden();
        $this->post('/hrd-accounts',$this->payload())->assertForbidden();
        $this->put('/administrator-branch-update',['branch_store_id'=>2])->assertForbidden();
        $this->get('/add-data')->assertForbidden();
    }
    public function test_disabled_branch_keeps_hrd_home_and_logout_available_but_blocks_employee_features(): void
    {
        $actor=$this->actor();DB::table('branch_stores')->where('id',1)->update(['face_enrollment_enabled'=>false]);
        $this->actingAs($actor)->get('/hrd')->assertOk()->assertSee('Hubungi Owner');
        $this->get('/employees')->assertNotFound();$this->get('/enroll')->assertNotFound();
        $this->actingAs($actor)->view('admin.includes.sidebar')->assertDontSee('Data Pegawai')->assertDontSee('Pendaftaran Wajah');
    }
    public function test_owner_can_create_update_and_soft_delete_hrd_without_creating_employee(): void
    {
        $this->actingAs($this->actor('OWNER'));
        $this->get('/hrd-accounts')->assertRedirect(route('staff.index',['page'=>'hrd']));
        $this->post('/hrd-accounts',$this->payload()+['role'=>'OWNER','branch_store_id'=>2])->assertRedirect(route('staff.index',['page'=>'hrd']))->assertSessionHasNoErrors();
        $hrd=User::where('email','new.hrd@example.com')->firstOrFail();
        $this->assertEquals('HRD',$hrd->role);$this->assertEquals(1,$hrd->branch_store_id);$this->assertTrue(Hash::check('SecretPass123',$hrd->password));
        $this->assertTrue($hrd->hasApplicationAccess('management'));$this->assertFalse($hrd->hasApplicationAccess('gym_landing'));
        $this->assertDatabaseCount('employees',0);
        $password=$hrd->password;
        $this->get('/hrd-accounts/'.$hrd->id.'/edit')->assertRedirect(route('staff.index',['page'=>'hrd','edit_account'=>$hrd->id]));
        $data=$this->payload();unset($data['password'],$data['password_confirmation']);$data['full_name']='Updated HRD';
        $this->put('/hrd-accounts/'.$hrd->id,$data+['role'=>'ADMIN','branch_store_id'=>2])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals($password,$hrd->fresh()->password);$this->assertEquals('HRD',$hrd->fresh()->role);$this->assertEquals(1,$hrd->fresh()->branch_store_id);
        $this->delete('/hrd-accounts/'.$hrd->id)->assertRedirect();$this->assertNotNull(User::withTrashed()->find($hrd->id)->deleted_at);
    }
    public function test_account_management_cannot_target_other_roles_or_another_branch(): void
    {
        $owner=$this->actor('OWNER');$other=$this->actor('HRD',2);$admin=$this->actor('ADMIN');
        $this->actingAs($owner);
        foreach([$other,$admin] as $target) {
            $this->get('/hrd-accounts/'.$target->id.'/edit')->assertNotFound();
            $this->put('/hrd-accounts/'.$target->id,$this->payload())->assertNotFound();
            $this->delete('/hrd-accounts/'.$target->id)->assertNotFound();
        }
        $this->actingAs($admin)->get('/hrd-accounts')->assertRedirect(route('staff.index',['page'=>'hrd']));
    }
    public function test_duplicate_email_and_bad_password_are_rejected_and_management_access_can_be_revoked(): void
    {
        $actor=$this->actor();$owner=$this->actor('OWNER');
        $this->actingAs($owner)->postJson('/hrd-accounts',array_merge($this->payload(),['email'=>$actor->email]))->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/hrd-accounts',array_merge($this->payload(),['password_confirmation'=>'different']))->assertStatus(422)->assertJsonValidationErrors('password');
        $actor->applicationAccesses()->update(['is_active'=>false]);
        $this->actingAs($actor)->get('/hrd')->assertRedirect(route('login'));$this->assertGuest();
        $this->post('/login',['email'=>$actor->email,'password'=>'SecretPass123'])->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
