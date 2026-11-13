<?php
namespace Tests\Feature;
use App\Models\{User,BranchStore,Employee};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
        require_once database_path('migrations/2026_11_05_000001_add_born_to_employees.php');(new \AddBornToEmployees)->up();
        require_once database_path('migrations/2026_11_13_000001_add_employment_details_to_employees.php');(new \AddEmploymentDetailsToEmployees)->up();
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
        $this->get('/employees?edit_employee='.$other->id)->assertNotFound();
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
    public function test_failed_create_reopens_add_modal_without_overwriting_edit_fields(): void
    {
        $employee=Employee::create($this->payload()+['branch_store_id'=>1]);
        $this->actingAs($this->actor())->from('/employees')->post('/employees',array_merge($this->payload(),[
            'employee_code'=>'NEW-CODE','full_name'=>'','_employee_id'=>'',
        ]))->assertRedirect('/employees')->assertSessionHasErrors('full_name');
        $response=$this->get('/employees')->assertOk()->assertSee('document.getElementById("modalAddEmployee")',false);
        $dom=new \DOMDocument();@$dom->loadHTML($response->getContent());
        $this->assertSame('NEW-CODE',$dom->getElementById('modalAddEmployeeCode')->getAttribute('value'));
        $this->assertSame('EMP001',$dom->getElementById('modalEditEmployee'.$employee->id.'Code')->getAttribute('value'));
        $this->assertSame('Pegawai baru',$dom->getElementById('modalEditEmployee'.$employee->id.'Name')->getAttribute('value'));
    }

    public function test_failed_update_reopens_correct_modal_even_when_employee_is_filtered_out(): void
    {
        $employee=Employee::create($this->payload()+['branch_store_id'=>1]);
        $this->actingAs($this->actor())->from('/employees?search=missing')->put('/employees/'.$employee->id,array_merge($this->payload(),[
            'employee_code'=>'EDITED-CODE','full_name'=>'','born'=>'1995-05-06','_employee_id'=>$employee->id,
        ]))->assertRedirect('/employees?search=missing')->assertSessionHasErrors('full_name');
        $response=$this->get('/employees?search=missing')->assertOk()
            ->assertSee('document.getElementById("modalEditEmployee'.$employee->id.'")',false);
        $this->assertCount(0,$response->viewData('employees')->items());
        $dom=new \DOMDocument();@$dom->loadHTML($response->getContent());
        $this->assertSame('EDITED-CODE',$dom->getElementById('modalEditEmployee'.$employee->id.'Code')->getAttribute('value'));
        $this->assertSame('',$dom->getElementById('modalEditEmployee'.$employee->id.'Name')->getAttribute('value'));
        $this->assertSame('1995-05-06',$dom->getElementById('modalEditEmployee'.$employee->id.'Born')->getAttribute('value'));
        $this->assertSame('',$dom->getElementById('modalAddEmployeeCode')->getAttribute('value'));
        $this->assertDatabaseHas('employees',['id'=>$employee->id,'employee_code'=>'EMP001','full_name'=>'Pegawai baru']);
    }

    public function test_employment_details_and_private_photos_can_be_created_and_viewed(): void
    {
        Storage::fake('local');
        $this->actingAs($this->actor())->post('/employees',$this->payload()+[
            'position_category'=>'FINANCE','employment_start_date'=>'2025-01-01','resign_date'=>'2026-11-01',
            'note'=>'Catatan <script>test</script>','photo'=>UploadedFile::fake()->image('pegawai.jpg'),
            'ktp_photo'=>UploadedFile::fake()->image('ktp.png'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $employee=Employee::firstOrFail();
        $this->assertSame('FINANCE',$employee->position_category);
        $this->assertSame('2025-01-01',$employee->employment_start_date->format('Y-m-d'));
        $this->assertSame('2026-11-01',$employee->resign_date->format('Y-m-d'));
        $this->assertSame('Catatan <script>test</script>',$employee->note);
        Storage::disk('local')->assertExists([$employee->photo_path,$employee->ktp_photo_path]);
        foreach (['photo','ktp'] as $kind) {
            $response=$this->get(route('employees.photo',[$employee->id,$kind]))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
            $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        }
        $this->get('/employees')->assertOk()->assertSee('Finance')->assertSee('01 Jan 2025')
            ->assertSee('Catatan &lt;script&gt;test&lt;/script&gt;',false)->assertSee('enctype="multipart/form-data"',false);
        $this->assertDatabaseCount('users',1);
    }

    public function test_update_keeps_existing_files_until_replacements_are_saved(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('employees/photo/old.jpg','old photo');
        Storage::disk('local')->put('employees/ktp_photo/old.jpg','old ktp');
        $employee=Employee::create($this->payload()+['branch_store_id'=>1,'photo_path'=>'employees/photo/old.jpg','ktp_photo_path'=>'employees/ktp_photo/old.jpg']);
        $this->actingAs($this->actor())->put('/employees/'.$employee->id,$this->payload()+[
            'position_category'=>'PT','employment_start_date'=>'2025-02-01','note'=>'Updated',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertSame('employees/photo/old.jpg',$employee->photo_path);
        $this->assertSame('employees/ktp_photo/old.jpg',$employee->ktp_photo_path);
        $this->put('/employees/'.$employee->id,$this->payload()+[
            'photo'=>UploadedFile::fake()->image('replacement.jpg'),'ktp_photo'=>UploadedFile::fake()->image('replacement-ktp.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $employee->refresh();
        Storage::disk('local')->assertMissing(['employees/photo/old.jpg','employees/ktp_photo/old.jpg']);
        Storage::disk('local')->assertExists([$employee->photo_path,$employee->ktp_photo_path]);
        $this->assertSame('PT',$employee->position_category);
        $this->delete('/employees/'.$employee->id)->assertRedirect();
        Storage::disk('local')->assertExists([$employee->photo_path,$employee->ktp_photo_path]);
        $this->get(route('employees.photo',[$employee->id,'ktp']))->assertNotFound();
    }

    public function test_employee_documents_require_branch_feature_and_role_access(): void
    {
        Storage::fake('local');Storage::disk('local')->put('employees/ktp_photo/test.jpg','test');
        $employee=Employee::create($this->payload()+['branch_store_id'=>1,'ktp_photo_path'=>'employees/ktp_photo/test.jpg']);
        $other=Employee::create(array_merge($this->payload(),['employee_code'=>'OTHER','branch_store_id'=>2,'ktp_photo_path'=>$employee->ktp_photo_path]));
        $this->get(route('employees.photo',[$employee->id,'ktp']))->assertRedirect(route('login'));
        $this->actingAs($this->actor())->get(route('employees.photo',[$other->id,'ktp']))->assertNotFound();
        $this->get(route('employees.photo',[$employee->id,'photo']))->assertNotFound();
        $this->get('/employees/'.$employee->id.'/photos/invalid')->assertNotFound();
        $this->actingAs($this->actor('CS'))->get(route('employees.photo',[$employee->id,'ktp']))->assertForbidden();
        $actor=$this->actor();$actor->branchStore->face_enrollment_enabled=false;
        $this->actingAs($actor)->get(route('employees.photo',[$employee->id,'ktp']))->assertNotFound();
    }

    public function test_validates_employment_category_dates_note_and_images(): void
    {
        Storage::fake('local');$this->actingAs($this->actor());
        foreach ([
            ['position_category'=>'OTHER'],['employment_start_date'=>'invalid'],['resign_date'=>'2026-02-30'],
            ['employment_start_date'=>'2026-11-01','resign_date'=>'2026-10-31'],['note'=>str_repeat('x',5001)],
            ['photo'=>UploadedFile::fake()->create('document.pdf',10,'application/pdf')],
            ['ktp_photo'=>UploadedFile::fake()->image('large.jpg')->size(5121)],
        ] as $extra) $this->postJson('/employees',array_merge($this->payload(),$extra))->assertUnprocessable();
        $this->assertDatabaseCount('employees',0);
        $this->assertCount(0,Storage::disk('local')->allFiles('employees'));
        foreach (['PT','CS','HR','FINANCE'] as $category) {
            $this->post('/employees',array_merge($this->payload(),['employee_code'=>$category,'position_category'=>$category]))->assertRedirect()->assertSessionHasNoErrors();
        }
    }

    public function test_failed_database_update_removes_new_upload_and_keeps_original(): void
    {
        Storage::fake('local');Storage::disk('local')->put('employees/photo/original.jpg','old');
        $employee=Employee::create($this->payload()+['branch_store_id'=>1,'photo_path'=>'employees/photo/original.jpg']);
        Schema::drop('face_attendance_employees');
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->actor())->put('/employees/'.$employee->id,$this->payload()+['photo'=>UploadedFile::fake()->image('new.jpg')]);
            $this->fail('Missing profile table should roll back the update.');
        } catch (\Illuminate\Database\QueryException $error) {
            $this->assertSame('employees/photo/original.jpg',$employee->fresh()->photo_path);
            $this->assertSame(['employees/photo/original.jpg'],Storage::disk('local')->allFiles('employees'));
        }
    }

    public function test_employment_migration_is_repeatable_and_preserves_legacy_data(): void
    {
        $employee=Employee::create($this->payload()+['branch_store_id'=>1]);
        $migration=new \AddEmploymentDetailsToEmployees;$migration->up();$migration->up();$migration->down();
        $employee->refresh();
        $this->assertSame('Kasir',$employee->position);
        $this->assertNull($employee->position_category);
        $this->assertNull($employee->photo_path);
        $this->assertNull($employee->employment_start_date);
    }

    public function test_birthday_can_be_saved_edited_and_cleared(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-05 10:00:00','Asia/Jakarta'));
        $this->actingAs($this->actor())->post('/employees',$this->payload()+['born'=>'1995-11-05'])->assertRedirect()->assertSessionHasNoErrors();
        $employee=Employee::firstOrFail();$this->assertEquals('1995-11-05',$employee->born->format('Y-m-d'));
        $this->get('/employees/'.$employee->id.'/edit')->assertOk()->assertSee('1995-11-05');
        $this->put('/employees/'.$employee->id,$this->payload()+['born'=>'1995-12-01'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals('1995-12-01',$employee->fresh()->born->format('Y-m-d'));
        $this->put('/employees/'.$employee->id,$this->payload()+['born'=>''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($employee->fresh()->born);
    }
    public function test_birthday_rejects_invalid_dates_and_future_dates(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-05 10:00:00','Asia/Jakarta'));
        $this->actingAs($this->actor());
        foreach(['2026-11-06','2001-02-29','not-a-date','31/12/2000'] as $born) {
            $this->postJson('/employees',$this->payload()+['born'=>$born])->assertStatus(422)->assertJsonValidationErrors('born');
        }
        $this->assertDatabaseCount('employees',0);
    }
    public function test_birthday_alerts_cover_all_active_branch_employees_independent_of_search_and_page(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-12-31 10:00:00','Asia/Jakarta'));
        foreach([
            ['name'=>'Birthday Today','born'=>'1990-12-31','branch'=>1,'active'=>1,'deleted'=>null],
            ['name'=>'Birthday Tomorrow','born'=>'1990-01-01','branch'=>1,'active'=>1,'deleted'=>null],
            ['name'=>'Birthday Two Days','born'=>'1990-01-02','branch'=>1,'active'=>1,'deleted'=>null],
            ['name'=>'Outside Window','born'=>'1990-01-03','branch'=>1,'active'=>1,'deleted'=>null],
            ['name'=>'Inactive Birthday','born'=>'1990-12-31','branch'=>1,'active'=>0,'deleted'=>null],
            ['name'=>'Other Branch Birthday','born'=>'1990-12-31','branch'=>2,'active'=>1,'deleted'=>null],
            ['name'=>'Deleted Birthday','born'=>'1990-12-31','branch'=>1,'active'=>1,'deleted'=>'2026-12-01'],
            ['name'=>'Unknown Birthday','born'=>null,'branch'=>1,'active'=>1,'deleted'=>null],
        ] as $i=>$row) {
            DB::table('employees')->insert(['employee_code'=>'E'.$i,'full_name'=>$row['name'],'born'=>$row['born'],
                'branch_store_id'=>$row['branch'],'is_active'=>$row['active'],'deleted_at'=>$row['deleted']]);
        }
        $response=$this->actingAs($this->actor())->get('/employees?search=no-matching-name&per_page=10&page=2')->assertOk();
        $response->assertViewHas('birthdayMessages',function($messages) {
            return array_values($messages[0])===['Birthday Today'] && array_values($messages[1])===['Birthday Tomorrow']
                && array_values($messages[2])===['Birthday Two Days'];
        });
        $response->assertSee('Hari ini ulang tahun')->assertSee('Birthday Today')->assertSee('Besok ulang tahun')
            ->assertSee('Birthday Tomorrow')->assertSee('Birthday Two Days')->assertDontSee('Inactive Birthday')
            ->assertDontSee('Other Branch Birthday')->assertDontSee('Deleted Birthday')->assertDontSee('Outside Window');
    }
    public function test_leap_day_birthdays_and_escaped_employee_names(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2027-02-28 10:00:00','Asia/Jakarta'));
        $employee=Employee::create(array_merge($this->payload(),['full_name'=>'<script>alert(1)</script>','born'=>'2000-02-29','branch_store_id'=>1]));
        $this->actingAs($this->actor())->get('/employees?search=missing')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)
            ->assertDontSee('<script>alert(1)</script>',false)->assertViewHas('birthdayMessages',function($messages) use($employee) { return isset($messages[1][$employee->id]); });
        $this->travelTo(\Carbon\Carbon::parse('2028-02-28 10:00:00','Asia/Jakarta'));
        $this->get('/employees')->assertOk()->assertViewHas('birthdayMessages',function($messages) use($employee) { return isset($messages[1][$employee->id]); });
        $this->travelTo(\Carbon\Carbon::parse('2028-03-01 10:00:00','Asia/Jakarta'));
        $this->get('/employees')->assertOk()->assertViewHas('birthdayMessages',[0=>[],1=>[],2=>[]]);
    }
    public function test_birthday_migration_is_repeatable_and_preserves_existing_employee(): void
    {
        $employee=Employee::create($this->payload()+['branch_store_id'=>1]);
        $migration=new \AddBornToEmployees;$migration->up();$migration->up();$migration->down();
        $this->assertNull($employee->fresh()->born);
        $this->assertDatabaseCount('employees',1);
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
