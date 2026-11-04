<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore,Employee};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class EmployeeManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'face_enrollment','database.connections.face_enrollment'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        DB::purge('face_enrollment');
        Schema::create('branch_stores',function(Blueprint $t) { $t->id();$t->string('name'); });
        Schema::create('users',function(Blueprint $t) { $t->id();$t->string('full_name'); });
        DB::table('users')->insert(['id'=>99,'full_name'=>'Login only']);
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        require_once database_path('migrations/2026_10_15_000001_create_face_enrollment_profiles.php');
        (new \CreateFaceEnrollmentProfiles)->up();
        require_once database_path('migrations/2026_11_04_000001_create_independent_employees.php');
        (new \CreateIndependentEmployees)->up();
    }
    private function actor(string $role='OWNER'): User
    {
        $actor=new User(['role'=>$role,'full_name'=>'Operator','branch_store_id'=>1]);$actor->id=7;
        $actor->setRelation('branchStore',new BranchStore(['name'=>'One','face_enrollment_enabled'=>true]));
        return $actor;
    }
    private function payload(): array
    {
        return ['employee_code'=>'EMP001','full_name'=>'Pegawai baru','position'=>'Kasir','phone'=>'08123456789','is_active'=>1];
    }
    public function test_create_update_and_soft_delete_never_create_login_user(): void
    {
        $this->actingAs($this->actor())->post('/employees',$this->payload()+['branch_store_id'=>2])->assertRedirect(route('employees.index'))->assertSessionHasNoErrors();
        $employee=Employee::firstOrFail();$this->assertEquals(1,$employee->branch_store_id);
        DB::table('face_attendance_employees')->insert(['source_employee_id'=>$employee->id,'employee_code'=>'EMP-'.$employee->id,'name'=>'Old name','department'=>'Old department']);
        $this->put('/employees/'.$employee->id,array_merge($this->payload(),['full_name'=>'Nama baru','is_active'=>0,'branch_store_id'=>2]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employees',['id'=>$employee->id,'full_name'=>'Nama baru','is_active'=>0,'branch_store_id'=>1]);
        $this->assertDatabaseHas('face_attendance_employees',['source_employee_id'=>$employee->id,'name'=>'Nama baru','department'=>'Kasir · One']);
        $this->delete('/employees/'.$employee->id)->assertRedirect();
        $this->assertNotNull(Employee::withTrashed()->findOrFail($employee->id)->deleted_at);
        $this->assertDatabaseCount('users',1);$this->assertDatabaseCount('face_attendance_employees',1);
    }
    public function test_cannot_list_edit_update_or_delete_another_branch_employee(): void
    {
        $other=Employee::create($this->payload()+['branch_store_id'=>2]);
        $this->actingAs($this->actor())->get('/employees?branch_store_id=2')->assertOk()->assertDontSee('Pegawai baru');
        $this->get('/employees/'.$other->id.'/edit')->assertNotFound();
        $this->put('/employees/'.$other->id,$this->payload())->assertNotFound();
        $this->delete('/employees/'.$other->id)->assertNotFound();
        $this->assertNull($other->fresh()->deleted_at);
    }
    public function test_only_enabled_branch_owner_and_admin_can_manage_employees(): void
    {
        $this->get('/employees')->assertRedirect(route('login'));
        foreach(['CS','FC','PT'] as $role) {
            $this->actingAs($this->actor($role))->postJson('/employees',$this->payload())->assertForbidden();
        }
        $actor=$this->actor();$actor->branchStore->face_enrollment_enabled=false;
        $this->actingAs($actor)->get('/employees')->assertNotFound();
        $this->postJson('/employees',$this->payload())->assertNotFound();
        $this->actingAs($actor)->view('admin.includes.sidebar')->assertDontSee('Data Pegawai');
        $this->actingAs($this->actor('ADMIN'))->post('/employees',$this->payload())->assertRedirect()->assertSessionHasNoErrors();
    }
    public function test_validates_unique_employee_code_and_filters_search(): void
    {
        Employee::create($this->payload()+['branch_store_id'=>1]);
        $this->actingAs($this->actor())->postJson('/employees',$this->payload())->assertStatus(422)->assertJsonValidationErrors('employee_code');
        $this->postJson('/employees',['employee_code'=>'X','is_active'=>1])->assertStatus(422)->assertJsonValidationErrors('full_name');
        $this->get('/employees?search=EMP001&per_page=50')->assertOk()->assertSee('Pegawai baru');
        $this->get('/employees?search=missing')->assertOk()->assertDontSee('Pegawai baru');
    }
    public function test_migration_can_create_profiles_on_database_without_attendance_tables(): void
    {
        Schema::drop('face_attendance_employees');
        $migration=new \CreateIndependentEmployees;$migration->up();$migration->up();
        $this->assertTrue(Schema::hasTable('face_attendance_employees'));
        $this->assertTrue(Schema::hasColumn('face_attendance_employees','source_employee_id'));
        $this->assertDatabaseCount('employees',0);
        $this->assertDatabaseCount('face_attendance_employees',0);
    }
    public function test_migration_keeps_old_profiles_unlinked_and_is_repeatable(): void
    {
        DB::table('face_attendance_employees')->insert(['source_user_id'=>99,'employee_code'=>'USER-99','name'=>'Historical','department'=>'Legacy','face_embedding'=>'[1]']);
        $migration=new \CreateIndependentEmployees;$migration->up();$migration->up();$migration->down();
        $this->assertDatabaseCount('employees',0);
        $this->assertDatabaseHas('face_attendance_employees',['source_user_id'=>99,'source_employee_id'=>null,'face_embedding'=>'[1]']);
        $this->assertDatabaseCount('users',1);
    }
}
