<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateFaceEnrollmentProfiles extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('face_enrollment');
        // Existing installations are upgraded by the following rename migration.
        if ($schema->hasTable('face_attendance_employees') || $schema->hasTable('face_demo_employees')) return;
        $schema->create('face_attendance_employees', function(Blueprint $t) {
            $t->id();
            $t->string('employee_code')->unique();
            $t->unsignedInteger('source_user_id')->nullable()->unique();
            $t->string('name');
            $t->string('department');
            $t->json('face_embedding')->nullable();
            $t->string('face_model')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        // Shared with attendance: rollback intentionally retains profiles and biometrics.
    }
}
