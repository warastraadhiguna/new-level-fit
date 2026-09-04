<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceivedAmountToPaymentTables extends Migration
{
    public function up()
    {
        Schema::table('member_registration_payments', function (Blueprint $table) {
            $table->unsignedInteger('received_amount')->nullable()->after('value');
        });

        Schema::table('trainer_session_payments', function (Blueprint $table) {
            $table->unsignedInteger('received_amount')->nullable()->after('value');
        });
    }

    public function down()
    {
        Schema::table('member_registration_payments', function (Blueprint $table) {
            $table->dropColumn('received_amount');
        });

        Schema::table('trainer_session_payments', function (Blueprint $table) {
            $table->dropColumn('received_amount');
        });
    }
}
