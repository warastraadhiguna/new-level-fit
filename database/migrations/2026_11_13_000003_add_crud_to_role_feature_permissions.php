<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddCrudToRoleFeaturePermissions extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_feature_permissions')) return;
        foreach(['create','read','update','delete'] as $action) {
            $column='can_'.$action;
            if (!Schema::hasColumn('role_feature_permissions',$column)) {
                Schema::table('role_feature_permissions',function(Blueprint $t) use($column) { $t->boolean($column)->nullable(); });
            }
        }
    }
    public function down(): void { /* Keep saved CRUD permissions on rollback. */ }
}
