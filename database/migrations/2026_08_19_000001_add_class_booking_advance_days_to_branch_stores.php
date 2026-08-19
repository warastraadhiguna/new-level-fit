<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClassBookingAdvanceDaysToBranchStores extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->unsignedTinyInteger('class_booking_advance_days')
                ->default(1)
                ->after('dashboard_finance_visible_roles');
        });
    }

    public function down()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn('class_booking_advance_days');
        });
    }
}
