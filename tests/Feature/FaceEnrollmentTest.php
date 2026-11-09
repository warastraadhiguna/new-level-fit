<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{DB,Http,Schema};
use Tests\TestCase;
class FaceEnrollmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'face_enrollment','database.connections.face_enrollment'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        DB::purge('face_enrollment');
        $schema=Schema::connection('face_enrollment');

        $schema->create('branch_stores', function(Blueprint $t) { $t->id();$t->string('name'); });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'Branch One'],['id'=>2,'name'=>'Branch Two']]);
        require_once database_path('migrations/2026_11_04_000001_create_independent_employees.php');
        (new \CreateIndependentEmployees)->up();
        DB::table('employees')->insert([
            ['id'=>10,'full_name'=>'Active One','employee_code'=>'EMP-10','position'=>'CS','is_active'=>true,'branch_store_id'=>1,'deleted_at'=>null],
            ['id'=>20,'full_name'=>'Active Two','employee_code'=>'EMP-20','position'=>'FC','is_active'=>true,'branch_store_id'=>2,'deleted_at'=>null],
            ['id'=>30,'full_name'=>'Deleted Account','employee_code'=>'EMP-30','position'=>'CS','is_active'=>true,'branch_store_id'=>1,'deleted_at'=>'2026-01-01'],
        ]);
        require_once database_path('migrations/2026_10_15_000001_create_face_enrollment_profiles.php');
        (new \CreateFaceEnrollmentProfiles)->up();
        (new \CreateIndependentEmployees)->up();
        require_once database_path('migrations/2026_11_04_000002_create_face_attendance_samples.php');(new \CreateFaceAttendanceSamples)->up();
    }
    private function actor(string $role='OWNER'): User
    {
        $user=new User(['role'=>$role,'full_name'=>'Operator','branch_store_id'=>1]);$user->id=1;
        $user->setRelation('branchStore',new BranchStore(['name'=>'Branch One','face_enrollment_enabled'=>true]));
        return $user;
    }
    private function body(int $id=10): array
    {
        return ['employee_id'=>$id,'photo'=>'data:image/jpeg;base64,'.base64_encode(file_get_contents(base_path('tests/Fixtures/camera.jpg')))];
    }
    private function fakeModel(): void
    {
        Http::fake(['*/face/enroll'=>Http::response(['success'=>true,'quality_checked'=>true,'face_count'=>1,
            'embedding'=>array_fill(0,128,0.1),'model'=>'opencv-sface-2021dec'])]);
    }
    public function test_guests_and_non_admins_cannot_enroll(): void
    {
        $this->get('/enroll')->assertRedirect(route('login'));
        $this->postJson('/enroll',$this->body())->assertUnauthorized();
        foreach(['CS','FC','PT'] as $role) {
            $this->actingAs($this->actor($role))->get('/enroll')->assertForbidden();
            $this->postJson('/enroll',$this->body())->assertForbidden();
        }
    }
    public function test_owner_page_shows_active_employees_and_sidebar_link(): void
    {
        $response = $this->actingAs($this->actor())->get('/enroll')->assertOk()->assertSee('Active One')->assertDontSee('Active Two')
            ->assertDontSee('Deleted Account')->assertSee('Pendaftaran Wajah');
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $links = (new \DOMXPath($document))->query('//li[a/span[normalize-space(.)="Staff"]]/ul/li/a');
        $this->assertCount(5, $links);
        $this->assertEquals('Staff List', trim($links->item(0)->textContent));
        $this->assertEquals('Akun HRD', trim($links->item(1)->textContent));
        $this->assertEquals('Data Pegawai', trim($links->item(2)->textContent));
        $this->assertEquals('Pendaftaran Wajah', trim($links->item(3)->textContent));
    }
    public function test_admin_is_limited_to_own_branch(): void
    {
        $this->actingAs($this->actor('ADMIN'))->get('/enroll')->assertOk()->assertSee('Active One')->assertDontSee('Active Two');
        Http::fake();
        $this->postJson('/enroll',$this->body(20))->assertNotFound();Http::assertNothingSent();
    }
    public function test_enrollment_adds_samples_without_replacing_old_ones(): void
    {
        $this->actingAs($this->actor());$this->fakeModel();
        $this->postJson('/enroll',$this->body())->assertOk();
        $this->postJson('/enroll',$this->body())->assertOk();
        $this->assertDatabaseCount('face_attendance_employees',1);
        $this->assertDatabaseCount('face_attendance_samples',2);
        $this->assertDatabaseHas('face_attendance_employees',['source_employee_id'=>10,'employee_code'=>'EMP-10','name'=>'Active One','face_model'=>'opencv-sface-2021dec']);
        $this->assertCount(128,json_decode(DB::table('face_attendance_employees')->value('face_embedding'),true));
        $this->assertDatabaseCount('employees',3);
    }
    public function test_deleted_employee_and_bad_image_are_rejected_before_inference(): void
    {
        $this->actingAs($this->actor());Http::fake();
        $this->postJson('/enroll',$this->body(30))->assertNotFound();
        $this->postJson('/enroll',['employee_id'=>10,'photo'=>'bad'])->assertStatus(422);Http::assertNothingSent();
        $this->assertDatabaseCount('face_attendance_employees',0);
    }
    public function test_multiple_faces_and_bad_model_response_do_not_overwrite_enrollment(): void
    {
        $this->actingAs($this->actor());
        Http::fake(['*'=>Http::sequence()
            ->push(['success'=>true,'quality_checked'=>true,'face_count'=>1,'embedding'=>array_fill(0,128,0.1),'model'=>'opencv-sface-2021dec'])
            ->push(['success'=>false,'face_count'=>2,'message'=>'Harus tepat satu wajah'])
            ->push(['success'=>true,'quality_checked'=>true,'face_count'=>1,'embedding'=>'bad','model'=>'opencv-sface-2021dec'])]);
        $this->postJson('/enroll',$this->body())->assertOk();
        $before=DB::table('face_attendance_employees')->value('face_embedding');
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/enroll',$this->body())->assertStatus(422);
        }
        $this->assertEquals($before,DB::table('face_attendance_employees')->value('face_embedding'));
    }
    public function test_inactive_employee_and_login_users_are_not_enrollment_candidates(): void
    {
        Schema::create('users',function(Blueprint $t) { $t->id();$t->string('full_name'); });
        DB::table('users')->insert(['id'=>99,'full_name'=>'Login Only']);
        DB::table('employees')->where('id',10)->update(['is_active'=>false]);
        $this->actingAs($this->actor())->get('/enroll')->assertOk()->assertDontSee('Active One')->assertDontSee('Login Only');
        Http::fake();
        $this->postJson('/enroll',$this->body(10))->assertNotFound();
        $this->postJson('/enroll',$this->body(99))->assertNotFound();
        Http::assertNothingSent();
    }
    public function test_deactivation_during_inference_does_not_save_profile(): void
    {
        $this->actingAs($this->actor());
        Http::fake(function() {
            DB::table('employees')->where('id',10)->update(['is_active'=>false]);
            return Http::response(['success'=>true,'quality_checked'=>true,'face_count'=>1,'embedding'=>array_fill(0,128,0.1),'model'=>config('face.model')]);
        });
        $this->postJson('/enroll',$this->body())->assertNotFound();
        $this->assertDatabaseCount('face_attendance_employees',0);
    }
    public function test_samples_are_limited_to_five_and_deleting_last_disables_enrollment(): void
    {
        $this->actingAs($this->actor());$this->fakeModel();
        for ($i=0;$i<5;$i++) $this->postJson('/enroll',$this->body()+['pose'=>'left'])->assertOk()->assertJsonPath('sample_count',$i+1);
        Http::fake();$this->postJson('/enroll',$this->body())->assertStatus(422);Http::assertNothingSent();
        $response=$this->getJson('/enroll/samples?employee_id=10')->assertOk()->assertJsonCount(5,'samples');
        $this->assertStringNotContainsString('embedding',$response->getContent());
        foreach ($response->json('samples') as $sample) $this->deleteJson('/enroll/samples/'.$sample['id'],['employee_id'=>10])->assertOk();
        $this->assertDatabaseCount('face_attendance_samples',0);
        $this->assertNull(DB::table('face_attendance_employees')->value('face_embedding'));
        $this->getJson('/enroll/samples?employee_id=10')->assertOk()->assertJsonPath('sample_count',0);
    }
    public function test_deleting_first_sample_keeps_others_and_migration_does_not_restore_it(): void
    {
        $this->actingAs($this->actor());$this->fakeModel();
        $this->postJson('/enroll',$this->body())->assertOk();
        $this->postJson('/enroll',$this->body()+['pose'=>'left'])->assertOk();
        $id=DB::table('face_attendance_samples')->where('slot',1)->value('id');
        $this->deleteJson('/enroll/samples/'.$id,['employee_id'=>10])->assertOk()->assertJsonPath('sample_count',1);
        (new \CreateFaceAttendanceSamples)->up();
        $this->assertDatabaseCount('face_attendance_samples',1);
        $this->assertDatabaseHas('face_attendance_samples',['slot'=>2]);
        $this->postJson('/enroll',$this->body())->assertOk()->assertJsonPath('sample_count',2);
        $this->assertDatabaseHas('face_attendance_samples',['slot'=>1]);
    }
    public function test_sample_listing_and_removal_cannot_cross_employee_or_branch(): void
    {
        $this->actingAs($this->actor());$this->fakeModel();$this->postJson('/enroll',$this->body())->assertOk();
        $id=DB::table('face_attendance_samples')->value('id');
        $this->getJson('/enroll/samples?employee_id=20')->assertNotFound();
        $this->deleteJson('/enroll/samples/'.$id,['employee_id'=>20])->assertNotFound();
        $actor=$this->actor();$actor->branch_store_id=2;$this->actingAs($actor);
        $this->deleteJson('/enroll/samples/'.$id,['employee_id'=>10])->assertNotFound();
        $this->assertDatabaseCount('face_attendance_samples',1);
    }
    public function test_different_identity_and_failed_quality_do_not_add_samples(): void
    {
        $this->actingAs($this->actor());
        Http::fake(['*'=>Http::sequence()
            ->push(['success'=>true,'quality_checked'=>true,'face_count'=>1,'embedding'=>array_fill(0,128,0.1),'model'=>config('face.model')])
            ->push(['success'=>true,'quality_checked'=>true,'face_count'=>1,'embedding'=>array_fill(0,128,-0.1),'model'=>config('face.model')])
            ->push(['success'=>false,'message'=>'Foto wajah buram.'],422)
            ->push(['success'=>true,'face_count'=>1,'embedding'=>array_fill(0,128,0.1),'model'=>config('face.model')])]);
        $this->postJson('/enroll',$this->body())->assertOk();
        for($i=0;$i<3;$i++) $this->postJson('/enroll',$this->body())->assertStatus(422);
        $this->assertDatabaseCount('face_attendance_samples',1);
    }
    public function test_migration_preserves_existing_embedding_as_first_sample_once(): void
    {
        DB::table('face_attendance_employees')->insert(['source_employee_id'=>10,'employee_code'=>'EMP-10','name'=>'Old','department'=>'CS','face_embedding'=>json_encode(array_fill(0,128,0.1)),'face_model'=>config('face.model')]);
        $migration=new \CreateFaceAttendanceSamples;$migration->up();$migration->up();
        $this->assertDatabaseCount('face_attendance_samples',1);
        $this->assertDatabaseHas('face_attendance_samples',['slot'=>1,'pose'=>'front']);
        $this->actingAs($this->actor())->getJson('/enroll/samples?employee_id=10')->assertOk()->assertJsonPath('sample_count',1);
    }
    public function test_service_offline_is_handled(): void
    {
        $this->actingAs($this->actor());Http::fake(function() { throw new ConnectionException('Offline'); });
        $this->postJson('/enroll',$this->body())->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('face_attendance_employees',0);
    }
    public function test_migration_does_not_drop_shared_profiles(): void
    {
        $this->actingAs($this->actor());$this->fakeModel();$this->postJson('/enroll',$this->body())->assertOk();
        $migration=new \CreateFaceEnrollmentProfiles;$migration->up();$migration->down();
        $this->assertDatabaseCount('face_attendance_employees',1);
    }
    public function test_owner_cannot_enroll_another_branch_even_with_forged_branch_input(): void
    {
        $this->actingAs($this->actor());Http::fake();
        $this->postJson('/enroll',$this->body(20)+['branch_store_id'=>2])->assertNotFound();
        Http::assertNothingSent();
        $this->assertDatabaseCount('face_attendance_employees',0);
    }
    public function test_selected_account_branch_controls_list_and_post(): void
    {
        foreach (['OWNER','ADMIN'] as $role) {
            $actor=$this->actor($role);$actor->branch_store_id=2;
            $this->actingAs($actor)->get('/enroll?branch_store_id=1')->assertOk()->assertSee('Active Two')->assertDontSee('Active One');
            Http::fake();$this->postJson('/enroll',$this->body(10))->assertNotFound();Http::assertNothingSent();
        }
    }
    public function test_missing_branch_does_not_expose_employees(): void
    {
        $actor=$this->actor();$actor->branch_store_id=null;
        $this->actingAs($actor)->get('/enroll')->assertForbidden();
        Http::fake();$this->postJson('/enroll',$this->body())->assertForbidden();Http::assertNothingSent();
    }
    public function test_disabled_feature_hides_menu_and_blocks_both_endpoints(): void
    {
        foreach (['OWNER','ADMIN'] as $role) {
            $actor=$this->actor($role);$actor->branchStore->face_enrollment_enabled=false;
            $this->actingAs($actor)->view('admin.includes.sidebar')->assertDontSee('Pendaftaran Wajah')->assertSee('Staff List');
            $this->get('/enroll')->assertNotFound();
            Http::fake();$this->postJson('/enroll',$this->body())->assertNotFound();Http::assertNothingSent();
        }
    }
    public function test_unset_branch_setting_is_disabled_by_default(): void
    {
        $actor=$this->actor();$actor->setRelation('branchStore',new BranchStore(['name'=>'New branch']));
        $this->assertFalse($actor->branchStore->face_enrollment_enabled);
        $this->actingAs($actor)->get('/enroll')->assertNotFound();
    }
}
