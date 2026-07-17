<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('member_registrations', 'payment_deadline')) {
            Schema::table('member_registrations', function (Blueprint $table) {
                $table->unsignedInteger('payment_deadline')->default(0)->after('admin_price');
            });
        }

        if (!Schema::hasColumn('trainer_sessions', 'payment_deadline')) {
            Schema::table('trainer_sessions', function (Blueprint $table) {
                $table->unsignedInteger('payment_deadline')->default(0)->after('admin_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('member_registrations', 'payment_deadline')) {
            Schema::table('member_registrations', function (Blueprint $table) {
                $table->dropColumn('payment_deadline');
            });
        }

        if (Schema::hasColumn('trainer_sessions', 'payment_deadline')) {
            Schema::table('trainer_sessions', function (Blueprint $table) {
                $table->dropColumn('payment_deadline');
            });
        }
    }
};
