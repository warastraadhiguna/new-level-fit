<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchSalesDiscounts extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->boolean('member_discount_enabled')->default(false)->after('member_installment_cancel_days');
            $table->boolean('trainer_discount_enabled')->default(false)->after('member_discount_enabled');
        });

        Schema::table('member_registrations', function (Blueprint $table) {
            $table->unsignedInteger('discount_amount')->default(0)->after('admin_price');
        });

        Schema::table('trainer_sessions', function (Blueprint $table) {
            $table->unsignedInteger('discount_amount')->default(0)->after('admin_price');
        });
    }

    public function down()
    {
        Schema::table('trainer_sessions', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });

        Schema::table('member_registrations', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });

        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn(['member_discount_enabled', 'trainer_discount_enabled']);
        });
    }
}
