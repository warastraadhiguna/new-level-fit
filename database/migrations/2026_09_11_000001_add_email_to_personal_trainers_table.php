<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('personal_trainers', 'email')) {
            Schema::table('personal_trainers', function (Blueprint $table): void {
                $table->string('email')->nullable()->unique()->after('full_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personal_trainers', 'email')) {
            Schema::table('personal_trainers', function (Blueprint $table): void {
                $table->dropUnique('personal_trainers_email_unique');
                $table->dropColumn('email');
            });
        }
    }
};
