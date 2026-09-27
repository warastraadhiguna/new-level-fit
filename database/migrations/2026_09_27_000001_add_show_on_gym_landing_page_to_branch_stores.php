<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShowOnGymLandingPageToBranchStores extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->boolean('show_on_gym_landing_page')
                ->default(true)
                ->after('pos_inventory_enabled');
        });
    }

    public function down()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn('show_on_gym_landing_page');
        });
    }
}
