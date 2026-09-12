<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pt_leave_days', 'member_leave_day_id')) {
            Schema::table('pt_leave_days', function (Blueprint $table): void {
                $table->integer('member_leave_day_id')
                    ->nullable()
                    ->after('trainer_session_id');
                $table->foreign('member_leave_day_id', 'pt_leave_days_member_leave_day_fk')
                    ->references('id')
                    ->on('leave_days')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pt_leave_days', 'member_leave_day_id')) {
            Schema::table('pt_leave_days', function (Blueprint $table): void {
                $table->dropForeign('pt_leave_days_member_leave_day_fk');
                $table->dropColumn('member_leave_day_id');
            });
        }
    }
};
