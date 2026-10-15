<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddFaceEnrollmentEnabledToBranchStores extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('branch_stores', 'face_enrollment_enabled')) {
            Schema::table('branch_stores', function(Blueprint $table) {
                $table->boolean('face_enrollment_enabled')->default(false);
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasColumn('branch_stores', 'face_enrollment_enabled')) {
            Schema::table('branch_stores', function(Blueprint $table) { $table->dropColumn('face_enrollment_enabled'); });
        }
    }
}
