<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrainerApprovalFeature extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('branch_stores', 'trainer_approval_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->boolean('trainer_approval_enabled')
                    ->default(false)
                    ->after('member_approval_enabled');
            });
        }

        if (!Schema::hasColumn('trainer_sessions', 'is_approved')) {
            Schema::table('trainer_sessions', function (Blueprint $table) {
                $table->boolean('is_approved')
                    ->default(false)
                    ->after('description');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('trainer_sessions', 'is_approved')) {
            Schema::table('trainer_sessions', function (Blueprint $table) {
                $table->dropColumn('is_approved');
            });
        }

        if (Schema::hasColumn('branch_stores', 'trainer_approval_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->dropColumn('trainer_approval_enabled');
            });
        }
    }
}
