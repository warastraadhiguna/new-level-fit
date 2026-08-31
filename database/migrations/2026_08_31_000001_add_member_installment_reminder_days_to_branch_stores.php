<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberInstallmentReminderDaysToBranchStores extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->unsignedTinyInteger('member_installment_reminder_days')
                ->default(7)
                ->after('member_installment_enabled');
        });
    }

    public function down()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn('member_installment_reminder_days');
        });
    }
}
