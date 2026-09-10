<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('branch_stores', 'pt_free_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->boolean('pt_free_enabled')->default(false)->after('trainer_discount_enabled');
            });
        }

        if (!Schema::hasColumn('trainer_sessions', 'is_pt_free')) {
            Schema::table('trainer_sessions', function (Blueprint $table) {
                $table->boolean('is_pt_free')->default(false)->after('trainer_package_id');
                $table->index(
                    ['branch_store_id', 'is_pt_free', 'start_date'],
                    'trainer_sessions_pt_free_lookup_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trainer_sessions', 'is_pt_free')) {
            Schema::table('trainer_sessions', function (Blueprint $table) {
                $table->dropIndex('trainer_sessions_pt_free_lookup_index');
                $table->dropColumn('is_pt_free');
            });
        }

        if (Schema::hasColumn('branch_stores', 'pt_free_enabled')) {
            Schema::table('branch_stores', function (Blueprint $table) {
                $table->dropColumn('pt_free_enabled');
            });
        }
    }
};
