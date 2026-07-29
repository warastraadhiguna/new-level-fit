<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberInstallmentFeature extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->boolean('member_installment_enabled')->default(false)->after('is_payment_strict');
            $table->unsignedTinyInteger('member_installment_grace_days')->default(7)->after('member_installment_enabled');
            $table->unsignedSmallInteger('member_installment_cancel_days')->default(30)->after('member_installment_grace_days');
        });

        Schema::table('member_packages', function (Blueprint $table) {
            $table->boolean('is_installment_plan')->default(false)->after('is_all_club');
            $table->unsignedInteger('installment_monthly_amount')->nullable()->after('is_installment_plan');
        });

        Schema::table('member_registrations', function (Blueprint $table) {
            $table->boolean('is_installment_plan')->default(false)->after('payment_deadline');
            $table->unsignedInteger('installment_monthly_amount')->nullable()->after('is_installment_plan');
            $table->string('installment_status', 20)->nullable()->after('installment_monthly_amount');
            $table->string('installment_deposit_status', 20)->nullable()->after('installment_status');
            $table->unsignedTinyInteger('installment_grace_days')->nullable()->after('installment_deposit_status');
            $table->unsignedSmallInteger('installment_cancel_days')->nullable()->after('installment_grace_days');
            $table->timestamp('installment_cancelled_at')->nullable()->after('installment_cancel_days');
        });

        Schema::create('member_registration_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_registration_id');
            $table->unsignedTinyInteger('month_number');
            $table->unsignedTinyInteger('payment_order');
            $table->string('type', 20)->default('monthly');
            $table->date('due_date');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['member_registration_id', 'month_number'], 'registration_installment_month_unique');
            $table->foreign('member_registration_id', 'registration_installment_registration_fk')
                ->references('id')->on('member_registrations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_registration_installments');

        Schema::table('member_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'is_installment_plan', 'installment_monthly_amount', 'installment_status',
                'installment_deposit_status', 'installment_grace_days',
                'installment_cancel_days', 'installment_cancelled_at',
            ]);
        });

        Schema::table('member_packages', function (Blueprint $table) {
            $table->dropColumn(['is_installment_plan', 'installment_monthly_amount']);
        });

        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn([
                'member_installment_enabled', 'member_installment_grace_days',
                'member_installment_cancel_days',
            ]);
        });
    }
}
