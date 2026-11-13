<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $db=DB::connection('face_enrollment');$schema=$db->getSchemaBuilder();
        if (!$schema->hasTable('employee_position_categories')) $schema->create('employee_position_categories',function(Blueprint $t) {
            $t->id();$t->string('code',20)->unique();$t->string('name',100);
            $t->boolean('is_active')->default(true);$t->timestamps();
        });
        foreach (['PT'=>'PT','CS'=>'CS','HR'=>'HR','FINANCE'=>'Finance'] as $code=>$name) {
            $db->table('employee_position_categories')->insertOrIgnore(['code'=>$code,'name'=>$name,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        // Retain any legacy/custom category already stored on employees.
        if ($schema->hasTable('employees') && $schema->hasColumn('employees','position_category')) {
            foreach ($db->table('employees')->whereNotNull('position_category')->where('position_category','<>','')->distinct()->pluck('position_category') as $code) {
                $db->table('employee_position_categories')->insertOrIgnore(['code'=>$code,'name'=>$code,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
    }
    public function down(): void { /* Keep categories referenced by employee records. */ }
};
