<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipTapTransferEnabledToBranchStores extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('branch_stores', 'tip_tap_transfer_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->boolean('tip_tap_transfer_enabled')
                    ->default(false)
                    ->after('pos_inventory_enabled');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('branch_stores', 'tip_tap_transfer_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->dropColumn('tip_tap_transfer_enabled');
            });
        }
    }
}
