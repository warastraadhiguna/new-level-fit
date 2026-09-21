<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRevenueReportVisibleRolesToBranchStores extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->text('revenue_report_visible_roles')
                ->nullable()
                ->after('dashboard_finance_visible_roles');
        });

        // Preserve the access behavior that was previously shared with Dashboard.
        DB::table('branch_stores')->update([
            'revenue_report_visible_roles' => DB::raw('dashboard_finance_visible_roles'),
        ]);
    }

    public function down()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn('revenue_report_visible_roles');
        });
    }
}
