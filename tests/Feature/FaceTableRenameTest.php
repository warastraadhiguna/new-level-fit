<?php
namespace Tests\Feature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class FaceTableRenameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.face_enrollment'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]]);
        DB::purge('face_enrollment');
        $schema=Schema::connection('face_enrollment');
        $schema->create('face_demo_employees',function(Blueprint $t) { $t->id();$t->text('face_embedding'); });
        $schema->create('face_demo_attendances',function(Blueprint $t) { $t->id();$t->foreignId('employee_id')->constrained('face_demo_employees');$t->string('photo_path'); });
        $schema->create('face_demo_migrations',function(Blueprint $t) { $t->id();$t->string('migration'); });
        DB::connection('face_enrollment')->table('face_demo_employees')->insert(['id'=>4,'face_embedding'=>'[1,2,3]']);
        DB::connection('face_enrollment')->table('face_demo_attendances')->insert(['id'=>8,'employee_id'=>4,'photo_path'=>'attendance/kept.jpg']);
        DB::connection('face_enrollment')->table('face_demo_migrations')->insert(['id'=>1,'migration'=>'existing']);
        require_once database_path('migrations/2026_10_15_000002_rename_face_attendance_tables.php');
    }
    public function test_rename_preserves_profiles_attendance_foreign_keys_and_history(): void
    {
        require_once database_path('migrations/2026_10_15_000001_create_face_enrollment_profiles.php');
        (new \CreateFaceEnrollmentProfiles)->up();
        $this->assertFalse(Schema::connection('face_enrollment')->hasTable('face_attendance_employees'));
        $migration=new \RenameFaceAttendanceTables;$migration->up();$migration->up();$migration->down();
        $db=DB::connection('face_enrollment');
        $this->assertEquals('[1,2,3]',$db->table('face_attendance_employees')->where('id',4)->value('face_embedding'));
        $this->assertEquals('attendance/kept.jpg',$db->table('face_attendance_records')->where('id',8)->value('photo_path'));
        $this->assertEquals('existing',$db->table('face_attendance_migrations')->value('migration'));
        $this->assertFalse(Schema::connection('face_enrollment')->hasTable('face_demo_employees'));
        $db->table('face_attendance_records')->insert(['id'=>9,'employee_id'=>4,'photo_path'=>'attendance/new.jpg']);
        $this->assertEquals(2,$db->table('face_attendance_records')->count());
    }
    public function test_conflicts_are_detected_before_any_table_is_renamed(): void
    {
        $schema=Schema::connection('face_enrollment');
        $schema->create('face_attendance_records',function(Blueprint $t) { $t->id(); });
        try { (new \RenameFaceAttendanceTables)->up();$this->fail('Expected conflict'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('Both',$e->getMessage()); }
        $this->assertTrue($schema->hasTable('face_demo_employees'));
        $this->assertFalse($schema->hasTable('face_attendance_employees'));
        $this->assertEquals(1,DB::connection('face_enrollment')->table('face_demo_attendances')->count());
    }
}
