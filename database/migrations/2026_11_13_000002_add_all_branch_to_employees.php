<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllBranchToEmployees extends Migration
{
    public function up(): void
    {
        $schema=Schema::connection('face_enrollment');
        if (!$schema->hasColumn('employees','all_branch')) {
            $schema->table('employees',function(Blueprint $t) { $t->boolean('all_branch')->default(false); });
        }
    }
    public function down(): void { /* Retain the employee branch-access setting on rollback. */ }
}
