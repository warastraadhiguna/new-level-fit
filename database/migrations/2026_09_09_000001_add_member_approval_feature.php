<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberApprovalFeature extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('branch_stores', 'member_approval_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->boolean('member_approval_enabled')
                    ->default(false)
                    ->after('member_discount_enabled');
            });
        }

        if (!Schema::hasColumn('member_registrations', 'is_approved')) {
            Schema::table('member_registrations', function (Blueprint $table) {
                $table->boolean('is_approved')
                    ->default(false)
                    ->after('description');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('member_registrations', 'is_approved')) {
            Schema::table('member_registrations', function (Blueprint $table) {
                $table->dropColumn('is_approved');
            });
        }

        if (Schema::hasColumn('branch_stores', 'member_approval_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->dropColumn('member_approval_enabled');
            });
        }
    }
}
