<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class AttendanceDeviceOwnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'face_enrollment','database.connections.face_enrollment'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);DB::purge('face_enrollment');
        Schema::create('branch_stores',function(Blueprint $t) { $t->id();$t->string('name'); });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        require_once database_path('migrations/2026_10_15_000004_create_face_attendance_devices.php');(new \CreateFaceAttendanceDevices)->up();
        require_once database_path('migrations/2026_10_15_000005_add_owner_login_context_to_attendance_devices.php');(new \AddOwnerLoginContextToAttendanceDevices)->up();
        require_once database_path('migrations/2026_10_16_000001_allow_multiple_attendance_devices_per_branch.php');(new \AllowMultipleAttendanceDevicesPerBranch)->up();
    }
    private function actor(string $role='OWNER'): User
    {
        $actor=new User(['role'=>$role,'full_name'=>'Operator','branch_store_id'=>1]);$actor->id=7;
        $actor->setRelation('branchStore',new BranchStore(['name'=>'One']));return $actor;
    }
    public function test_guests_and_all_non_owners_are_blocked(): void
    {
        $this->get('/attendance-device')->assertRedirect(route('login'));
        foreach(['ADMIN','CS','FC','PT','CSPOS'] as $role) {
            $this->actingAs($this->actor($role))->get('/attendance-device')->assertForbidden();
            $this->post('/attendance-device/pair',['label'=>'HP'])->assertForbidden();
            $this->post('/attendance-device/revoke')->assertForbidden();
        }
        $this->assertDatabaseCount('face_attendance_devices',0);
        $this->actingAs($this->actor('ADMIN'))->view('admin.includes.sidebar')->assertDontSee('Perangkat Absensi');
    }
    public function test_owner_creates_independent_devices_scoped_to_current_branch(): void
    {
        $this->actingAs($this->actor());
        $this->post('/attendance-device/pair',['label'=>'Front Desk','branch_store_id'=>2])->assertRedirect(route('attendance-device.index'));
        $code=str_replace('-','',session('pairing_code'));$this->assertEquals(12,strlen($code));
        $row=DB::table('face_attendance_devices')->first();$this->assertEquals(1,$row->branch_store_id);
        $this->assertEquals(hash('sha256',$code),$row->pairing_hash);
        $this->assertStringNotContainsString($code,json_encode($row));
        DB::table('face_attendance_devices')->where('id',$row->id)->update(['token_hash'=>hash('sha256','existing'),'label'=>'Existing HP','expires_at'=>now('UTC')->addYear()]);
        $this->post('/attendance-device/pair',['label'=>'Replacement'])->assertRedirect();
        $new=DB::table('face_attendance_devices')->orderByDesc('id')->first();$this->assertNotEquals($row->pairing_hash,$new->pairing_hash);
        $existing=DB::table('face_attendance_devices')->where('id',$row->id)->first();
        $this->assertEquals(hash('sha256','existing'),$existing->token_hash);$this->assertEquals('Existing HP',$existing->label);
        $this->assertNull($new->token_hash);
        $this->assertDatabaseCount('face_attendance_devices',2);
        $this->get('/attendance-device')->assertOk()->assertSee('Existing HP');
    }
    public function test_revoke_only_current_branch_device_and_pending_code(): void
    {
        foreach([1,2] as $branch)DB::table('face_attendance_devices')->insert(['branch_store_id'=>$branch,'token_hash'=>hash('sha256','token'.$branch),'pairing_hash'=>hash('sha256','code'.$branch)]);
        $this->actingAs($this->actor())->post('/attendance-device/revoke',['branch_store_id'=>2,'device_id'=>1])->assertRedirect();
        $row=DB::table('face_attendance_devices')->where('branch_store_id',1)->first();$this->assertNull($row->token_hash);$this->assertNull($row->pairing_hash);
        $this->assertEquals(hash('sha256','token2'),DB::table('face_attendance_devices')->where('branch_store_id',2)->value('token_hash'));
    }
    public function test_revoke_targets_only_selected_device_and_rejects_other_branches(): void
    {
        foreach([1,1,2] as $i=>$branch) DB::table('face_attendance_devices')->insert(['branch_store_id'=>$branch,'token_hash'=>hash('sha256','token'.$i)]);
        $this->actingAs($this->actor())->post('/attendance-device/revoke',['device_id'=>3])->assertNotFound();
        $this->post('/attendance-device/revoke')->assertSessionHasErrors('device_id');
        $this->post('/attendance-device/revoke',['device_id'=>1])->assertRedirect();
        $this->assertNull(DB::table('face_attendance_devices')->where('id',1)->value('token_hash'));
        $this->assertEquals(hash('sha256','token1'),DB::table('face_attendance_devices')->where('id',2)->value('token_hash'));
        $this->assertEquals(hash('sha256','token2'),DB::table('face_attendance_devices')->where('id',3)->value('token_hash'));
    }
    public function test_list_only_shows_active_or_pending_devices_in_current_branch(): void
    {
        $now=now('UTC');
        DB::table('face_attendance_devices')->insert([
            ['branch_store_id'=>1,'label'=>'Current phone','token_hash'=>hash('sha256','active'),'expires_at'=>$now->copy()->addYear()],
            ['branch_store_id'=>1,'label'=>'Expired phone','token_hash'=>hash('sha256','expired'),'expires_at'=>$now->copy()->subSecond()],
        ]);
        DB::table('face_attendance_devices')->insert(['branch_store_id'=>1,'label'=>'Revoked phone']);
        DB::table('face_attendance_devices')->insert(['branch_store_id'=>1,'pairing_label'=>'Pending phone','pairing_hash'=>hash('sha256','pending'),'pairing_expires_at'=>$now->copy()->addMinutes(5)]);
        DB::table('face_attendance_devices')->insert(['branch_store_id'=>1,'pairing_label'=>'Expired code','pairing_hash'=>hash('sha256','expired-code'),'pairing_expires_at'=>$now->copy()->subSecond()]);
        DB::table('face_attendance_devices')->insert(['branch_store_id'=>2,'label'=>'Other branch','token_hash'=>hash('sha256','other'),'expires_at'=>$now->copy()->addYear()]);
        $this->actingAs($this->actor())->get('/attendance-device')->assertOk()
            ->assertSee('Current phone')->assertSee('Pending phone')->assertDontSee('Expired phone')
            ->assertDontSee('Revoked phone')->assertDontSee('Expired code')->assertDontSee('Other branch');
        $this->post('/attendance-device/revoke',['device_id'=>1])->assertRedirect();
        $this->get('/attendance-device')->assertOk()->assertDontSee('Current phone')->assertSee('Pending phone');
    }
    public function test_owner_login_branch_snapshot_ignores_input_and_survives_owner_branch_change(): void
    {
        $owner=$this->actor();$this->actingAs($owner);
        $this->post('/attendance-device/pair',['label'=>'HP One','owner_login_branch_store_id'=>2,'owner_login_branch_name'=>'Forged'])->assertRedirect();
        $row=DB::table('face_attendance_devices')->where('branch_store_id',1)->first();
        $this->assertEquals(7,$row->owner_id);$this->assertEquals('Operator',$row->owner_name);
        $this->assertEquals(1,$row->owner_login_branch_store_id);$this->assertEquals('One',$row->owner_login_branch_name);$this->assertNotNull($row->owner_recorded_at);
        $this->get('/attendance-device')->assertOk()->assertSee('Cabang login Owner:')->assertSee('One');
        $owner->branch_store_id=2;$owner->setRelation('branchStore',new BranchStore(['name'=>'Two']));
        $this->actingAs($owner)->post('/attendance-device/pair',['label'=>'HP Two'])->assertRedirect();
        $this->assertEquals('One',DB::table('face_attendance_devices')->where('branch_store_id',1)->value('owner_login_branch_name'));
        $this->assertEquals(2,DB::table('face_attendance_devices')->where('branch_store_id',2)->value('owner_login_branch_store_id'));
        $this->post('/attendance-device/revoke',['owner_login_branch_store_id'=>1,'device_id'=>2])->assertRedirect();
        $this->assertEquals('Two',DB::table('face_attendance_devices')->where('branch_store_id',2)->value('owner_login_branch_name'));
    }
}
