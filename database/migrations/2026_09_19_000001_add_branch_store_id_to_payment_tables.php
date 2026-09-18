<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBranchStoreIdToPaymentTables extends Migration
{
    public function up()
    {
        Schema::table('member_registration_payments', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_store_id')->nullable()->after('id');
            $table->index('branch_store_id', 'member_registration_payments_branch_index');
        });

        Schema::table('trainer_session_payments', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_store_id')->nullable()->after('id');
            $table->index('branch_store_id', 'trainer_session_payments_branch_index');
        });

        DB::statement(<<<'SQL'
UPDATE member_registration_payments AS payment
INNER JOIN member_registrations AS registration ON registration.id = payment.member_registration_id
INNER JOIN members AS member ON member.id = registration.member_id
LEFT JOIN users AS staff ON staff.id = payment.user_id
SET payment.branch_store_id = COALESCE(staff.branch_store_id, member.branch_store_id)
WHERE payment.branch_store_id IS NULL
SQL
        );

        DB::statement(<<<'SQL'
UPDATE trainer_session_payments AS payment
INNER JOIN trainer_sessions AS session ON session.id = payment.trainer_session_id
LEFT JOIN users AS staff ON staff.id = payment.user_id
SET payment.branch_store_id = COALESCE(staff.branch_store_id, session.branch_store_id)
WHERE payment.branch_store_id IS NULL
SQL
        );

        Schema::table('member_registration_payments', function (Blueprint $table) {
            $table->foreign('branch_store_id', 'member_registration_payments_branch_fk')
                ->references('id')->on('branch_stores')->onDelete('set null');
        });

        Schema::table('trainer_session_payments', function (Blueprint $table) {
            $table->foreign('branch_store_id', 'trainer_session_payments_branch_fk')
                ->references('id')->on('branch_stores')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('member_registration_payments', function (Blueprint $table) {
            $table->dropForeign('member_registration_payments_branch_fk');
            $table->dropIndex('member_registration_payments_branch_index');
            $table->dropColumn('branch_store_id');
        });

        Schema::table('trainer_session_payments', function (Blueprint $table) {
            $table->dropForeign('trainer_session_payments_branch_fk');
            $table->dropIndex('trainer_session_payments_branch_index');
            $table->dropColumn('branch_store_id');
        });
    }
}
