<?php
namespace Tests\Feature;

use App\Models\{Employee,User,BranchStore};
use App\Services\EmployeeAttendanceReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;

class EmployeeAttendanceManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'face_enrollment','database.connections.face_enrollment'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);DB::purge('face_enrollment');
        Schema::create('branch_stores',function(Blueprint $t) { $t->id();$t->string('name'); });
        Schema::create('users',function(Blueprint $t) { $t->id();$t->string('full_name'); });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        foreach (['2026_10_15_000001_create_face_enrollment_profiles.php'=>'CreateFaceEnrollmentProfiles','2026_11_04_000001_create_independent_employees.php'=>'CreateIndependentEmployees',
            '2026_11_13_000001_add_employment_details_to_employees.php'=>'AddEmploymentDetailsToEmployees','2026_11_13_000002_add_all_branch_to_employees.php'=>'AddAllBranchToEmployees',
            '2026_11_10_000001_create_role_feature_permissions.php'=>'CreateRoleFeaturePermissions',
            '2026_11_13_000003_add_crud_to_role_feature_permissions.php'=>'AddCrudToRoleFeaturePermissions'] as $file=>$class) {
            require_once database_path('migrations/'.$file);(new $class)->up();
        }
        Schema::create('face_attendance_records',function(Blueprint $t) {
            $t->id();$t->unsignedBigInteger('employee_id');$t->unsignedInteger('branch_store_id');$t->string('type');$t->date('attendance_date');$t->dateTime('attended_at');
        });
        $migration=require database_path('migrations/2026_11_14_000001_create_employee_attendance_management.php');$migration->up();$migration->up();
        Employee::create(['id'=>1,'employee_code'=>'EMP1','full_name'=>'Andi','is_active'=>true,'branch_store_id'=>1]);
        Employee::create(['id'=>2,'employee_code'=>'EMP2','full_name'=>'Other','is_active'=>true,'branch_store_id'=>2]);
    }
    private function actor(string $role='OWNER', int $branch=1): User
    {
        $u=new User(['role'=>$role,'full_name'=>'Operator','branch_store_id'=>$branch]);$u->id=7;
        $u->setRelation('branchStore',new BranchStore(['name'=>'One','face_enrollment_enabled'=>true]));return $u;
    }
    private function template(string $start='06:00', string $end='13:00'): int
    {
        $this->actingAs($this->actor())->post('/employee-shifts',['name'=>'Pagi','start_time'=>$start,'end_time'=>$end,'grace_minutes'=>5,'is_active'=>1])->assertRedirect()->assertSessionHasNoErrors();
        return DB::table('employee_shift_templates')->max('id');
    }
    private function schedule(int $template, array $data=[]): array
    {
        return $data+['employee_ids'=>[1],'start_date'=>'2026-11-13','end_date'=>'2026-11-13','weekdays'=>[1,2,3,4,5,6,7],'template_id'=>$template,'is_day_off'=>0];
    }
    public function test_templates_bulk_schedule_snapshot_overnight_and_rendering(): void
    {
        $id=$this->template('22:00','06:00');
        $this->post('/employee-schedules',$this->schedule($id))->assertRedirect()->assertSessionHasNoErrors();
        $s=DB::table('employee_shift_schedules')->first();$this->assertSame('2026-11-14 06:00:00',$s->ends_at);
        $this->put('/employee-shifts/'.$id,['name'=>'Updated','start_time'=>'21:00','end_time'=>'05:00','grace_minutes'=>10,'is_active'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2026-11-13 22:00:00',DB::table('employee_shift_schedules')->value('starts_at'));
        foreach (['employee-shifts','employee-schedules','employee-leaves','employee-attendance'] as $url) $this->get('/'.$url.'?from=2026-11-13&to=2026-11-14')->assertOk();
        $this->delete('/employee-shifts/'.$id)->assertSessionHasErrors('attendance');
        $this->postJson('/employee-schedules',$this->schedule($id))->assertStatus(422);
        $this->delete('/employee-schedules/'.$s->id)->assertRedirect()->assertSessionHasNoErrors();
        $this->delete('/employee-shifts/'.$id)->assertSessionHasNoErrors();
    }
    public function test_bulk_assignment_is_atomic_and_mobile_employee_has_one_schedule_across_branches(): void
    {
        $id=$this->template();
        $this->postJson('/employee-schedules',$this->schedule($id,['employee_ids'=>[1,2]]))->assertNotFound();
        $this->assertDatabaseCount('employee_shift_schedules',0);
        Employee::find(1)->update(['all_branch'=>true]);
        $this->post('/employee-schedules',$this->schedule($id))->assertSessionHasNoErrors();
        $this->actingAs($this->actor('OWNER',2))->postJson('/employee-schedules',$this->schedule($id,['is_day_off'=>1]))->assertStatus(422);
        $this->delete('/employee-schedules/1')->assertNotFound();
        $this->putJson('/employee-shifts/'.$id,['name'=>'Hack','start_time'=>'01:00','end_time'=>'02:00','grace_minutes'=>0,'is_active'=>1])->assertNotFound();
    }
    public function test_leave_review_overlap_cancellation_and_attendance_lock(): void
    {
        $this->actingAs($this->actor());
        $data=['employee_id'=>1,'type'=>'cuti','start_date'=>'2026-11-13','end_date'=>'2026-11-14','reason'=>'Keluarga'];
        $this->post('/employee-leaves',$data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_attendance_leaves',['status'=>'pending','created_by'=>7]);
        $this->postJson('/employee-leaves',$data)->assertStatus(422);
        $this->put('/employee-leaves/1',['status'=>'approved','review_note'=>'OK'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_attendance_leaves',['status'=>'approved','reviewed_by'=>7]);
        $this->putJson('/employee-leaves/1',['status'=>'rejected'])->assertStatus(422);
        $this->put('/employee-leaves/1',['status'=>'cancelled'])->assertSessionHasNoErrors();
        DB::table('face_attendance_employees')->insert(['id'=>1,'source_employee_id'=>1,'employee_code'=>'EMP1','name'=>'Andi','department'=>'CS']);
        DB::table('face_attendance_records')->insert(['employee_id'=>1,'branch_store_id'=>1,'type'=>'check_in','attended_at'=>'2026-11-13 06:10:00','attendance_date'=>'2026-11-13']);
        $this->postJson('/employee-leaves',$data)->assertStatus(422);
        $id=$this->template();$this->postJson('/employee-schedules',$this->schedule($id))->assertStatus(422);
    }
    public function test_recap_absence_leave_unscheduled_and_csv(): void
    {
        $id=$this->template();$this->post('/employee-schedules',$this->schedule($id))->assertSessionHasNoErrors();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-15 10:00:00','Asia/Jakarta'));
        $rows=app(EmployeeAttendanceReport::class)->rows(Employee::where('id',1)->get(),1,'2026-11-13','2026-11-14');
        $this->assertSame('Tidak hadir',$rows[0]['status']);$this->assertSame('Tanpa jadwal',$rows[1]['status']);
        $this->post('/employee-leaves',['employee_id'=>1,'type'=>'izin','start_date'=>'2026-11-14','end_date'=>'2026-11-14','reason'=>'Izin'])->assertSessionHasNoErrors();
        $this->put('/employee-leaves/1',['status'=>'approved'])->assertSessionHasNoErrors();
        $this->get('/employee-attendance?from=2026-11-13&to=2026-11-14')->assertOk()->assertSee('Tidak hadir')->assertSee('Izin');
        Employee::find(1)->update(['full_name'=>'=FORMULA()']);
        $response=$this->get('/employee-attendance/export?from=2026-11-13&to=2026-11-14')->assertOk();
        $this->assertStringContainsString("'=FORMULA()",$response->streamedContent());
        $this->getJson('/employee-attendance?from=2026-01-01&to=2026-12-31')->assertStatus(422);
    }
    public function test_branch_gate_and_role_defaults(): void
    {
        $this->get('/employee-attendance')->assertRedirect(route('login'));
        foreach (['CS','FINANCE','PT'] as $role) $this->actingAs($this->actor($role))->get('/employee-attendance')->assertForbidden();
        $this->actingAs($this->actor('HRD'))->get('/employee-attendance')->assertOk();
        $u=$this->actor();$u->branchStore->face_enrollment_enabled=false;
        $this->actingAs($u)->get('/employee-attendance')->assertNotFound();
        $this->post('/employee-shifts',['name'=>'Denied'])->assertNotFound();
    }

    public function test_granular_permissions_prevent_review_and_branch_switch_hides_features(): void
    {
        DB::table('role_feature_permissions')->insert(['branch_store_id'=>1,'role'=>'FINANCE','feature_key'=>'attendance_leaves','is_allowed'=>1,
            'can_read'=>1,'can_create'=>1,'can_update'=>0,'can_delete'=>0]);
        $u=$this->actor('FINANCE');$this->actingAs($u)->get('/employee-leaves')->assertOk();
        $this->post('/employee-leaves',['employee_id'=>1,'type'=>'izin','start_date'=>'2026-11-13','end_date'=>'2026-11-13','reason'=>'Izin'])->assertSessionHasNoErrors();
        $this->putJson('/employee-leaves/1',['status'=>'approved'])->assertForbidden();
        $this->get('/employee-shifts')->assertForbidden();
        $this->actingAs($this->actor('HRD'))->view('admin.includes.sidebar')->assertSee('Shift template')->assertSee('Attendance Report');
        $u=$this->actor();$u->branchStore->face_enrollment_enabled=false;
        $features=app(\App\Support\RoleFeatureAccess::class)->featuresForBranch($u->branchStore);
        $this->assertArrayNotHasKey('attendance_shifts',$features);$this->assertArrayNotHasKey('attendance_leaves',$features);
        $this->actingAs($u)->view('admin.includes.sidebar')->assertDontSee('Shift template')->assertDontSee('Attendance Report');
    }
    public function test_recap_uses_paired_mobile_records_and_used_schedule_is_locked(): void
    {
        Employee::find(1)->update(['all_branch'=>true]);$id=$this->template();
        $this->post('/employee-schedules',$this->schedule($id))->assertSessionHasNoErrors();
        DB::table('face_attendance_employees')->insert(['id'=>1,'source_employee_id'=>1,'employee_code'=>'EMP1','name'=>'Andi','department'=>'CS']);
        foreach (['check_in'=>[2,'2026-11-13 06:12:00',7,0],'check_out'=>[1,'2026-11-13 12:50:00',0,10]] as $type=>$data) {
            DB::table('face_attendance_records')->insert(['employee_id'=>1,'branch_store_id'=>$data[0],'type'=>$type,'attendance_date'=>'2026-11-13',
                'attended_at'=>$data[1],'schedule_id'=>1,'scheduled_start'=>'2026-11-13 06:00:00','scheduled_end'=>'2026-11-13 13:00:00','late_minutes'=>$data[2],'early_leave_minutes'=>$data[3]]);
        }
        $this->deleteJson('/employee-schedules/1')->assertStatus(422);
        foreach ([1,2] as $branch) {
            $rows=app(EmployeeAttendanceReport::class)->rows(Employee::where('id',1)->get(),$branch,'2026-11-13','2026-11-13');
            $this->assertSame('Terlambat',$rows[0]['status']);$this->assertSame(398,$rows[0]['work_minutes']);
            $this->assertSame(7,$rows[0]['late']);$this->assertSame(10,$rows[0]['early']);
        }
    }

    public function test_overnight_overlap_and_cleanup_for_inactive_employee(): void
    {
        $night=$this->template('22:00','06:00');$morning=$this->template('05:00','13:00');
        $this->post('/employee-schedules',$this->schedule($night))->assertSessionHasNoErrors();
        $this->postJson('/employee-schedules',$this->schedule($morning,['start_date'=>'2026-11-14','end_date'=>'2026-11-14']))->assertStatus(422);
        $this->assertDatabaseCount('employee_shift_schedules',1);
        $this->post('/employee-leaves',['employee_id'=>1,'type'=>'izin','start_date'=>'2026-11-15','end_date'=>'2026-11-15','reason'=>'Izin'])->assertSessionHasNoErrors();
        Employee::find(1)->update(['is_active'=>false]);
        $this->put('/employee-leaves/1',['status'=>'cancelled'])->assertSessionHasNoErrors();
        $this->delete('/employee-schedules/1')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employee_shift_schedules',0);
    }
}
