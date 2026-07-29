<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeTrainerSessionFcNullable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE `trainer_sessions` MODIFY `fc_id` INT(10) UNSIGNED NULL');
    }

    public function down()
    {
        DB::table('trainer_sessions')
            ->whereNull('fc_id')
            ->update(['fc_id' => DB::raw('`user_id`')]);

        DB::statement('ALTER TABLE `trainer_sessions` MODIFY `fc_id` INT(10) UNSIGNED NOT NULL');
    }
}
