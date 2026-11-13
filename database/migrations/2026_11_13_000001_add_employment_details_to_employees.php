<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmploymentDetailsToEmployees extends Migration
{
    public function up(): void
    {
        $schema=Schema::connection('face_enrollment');
        foreach (['photo_path','ktp_photo_path','position_category','employment_start_date','resign_date','note'] as $column) {
            if ($schema->hasColumn('employees',$column)) continue;
            $schema->table('employees',function(Blueprint $t) use($column) {
                if (in_array($column,['employment_start_date','resign_date'],true)) $t->date($column)->nullable();
                elseif ($column==='note') $t->text($column)->nullable();
                else $t->string($column,$column==='position_category' ? 20 : 255)->nullable();
            });
        }
    }

    public function down(): void { /* Retain employee documents and employment history on rollback. */ }
}
