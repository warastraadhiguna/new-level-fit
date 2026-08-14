<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->string('status', 20)
                ->default('published')
                ->after('is_active')
                ->index();
        });

        // Existing sessions inherit "published" from the ADD COLUMN default.
        // All sessions created after this migration must start safely as drafts.
        DB::statement(
            "ALTER TABLE `class_sessions` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
