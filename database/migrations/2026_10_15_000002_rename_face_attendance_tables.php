<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
class RenameFaceAttendanceTables extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('face_enrollment');
        $tables = [
            'face_demo_employees' => 'face_attendance_employees',
            'face_demo_attendances' => 'face_attendance_records',
            'face_demo_migrations' => 'face_attendance_migrations',
        ];
        // Check every conflict before renaming anything. Never merge/drop populated tables.
        foreach ($tables as $old => $new) {
            if ($schema->hasTable($old) && $schema->hasTable($new)) {
                throw new RuntimeException('Both '.$old.' and '.$new.' exist. Resolve table naming before continuing.');
            }
        }
        foreach ($tables as $old => $new) {
            if ($schema->hasTable($old)) $schema->rename($old, $new);
        }
    }
    public function down(): void
    {
        // Profiles and attendance are shared: keep data and current names during rollback.
    }
}
