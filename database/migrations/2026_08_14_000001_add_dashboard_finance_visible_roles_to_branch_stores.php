<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDashboardFinanceVisibleRolesToBranchStores extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->text('dashboard_finance_visible_roles')
                ->nullable()
                ->after('pos_inventory_enabled');
        });

        DB::table('branch_stores')->update([
            'dashboard_finance_visible_roles' => json_encode(['ALL']),
        ]);
    }

    public function down()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn('dashboard_finance_visible_roles');
        });
    }
}
