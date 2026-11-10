<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateRoleFeaturePermissions extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_feature_permissions')) return;
        Schema::create('role_feature_permissions',function(Blueprint $t) {
            $t->bigIncrements('id');$t->unsignedInteger('branch_store_id');
            $t->string('role',30);$t->string('feature_key',80);$t->boolean('is_allowed')->default(false);
            $t->unsignedInteger('updated_by')->nullable();$t->timestamp('updated_at')->nullable();
            $t->unique(['branch_store_id','role','feature_key'],'role_feature_permissions_branch_role_feature_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('role_feature_permissions'); }
}
