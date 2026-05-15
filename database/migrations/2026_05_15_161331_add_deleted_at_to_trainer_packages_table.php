<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('trainer_packages', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            if (Schema::hasColumn('trainer_packages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
