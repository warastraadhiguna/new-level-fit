<?php

use App\Support\ApplicationAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserApplicationAccessTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_application_access')) {
            Schema::create('user_application_access', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('application_code', 50);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'application_code'], 'user_application_access_unique');
                $table->index(['application_code', 'is_active'], 'user_application_access_app_active_index');
                $table->foreign('user_id', 'user_application_access_user_fk')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        $now = now();

        DB::table('users')
            ->select('id')
            ->where('role', 'ADMIN')
            ->orderBy('id')
            ->chunkById(500, function ($admins) use ($now) {
                $rows = [];

                foreach ($admins as $admin) {
                    foreach (ApplicationAccess::ADMIN_APPLICATIONS as $applicationCode) {
                        $rows[] = [
                            'user_id' => $admin->id,
                            'application_code' => $applicationCode,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows) {
                    DB::table('user_application_access')->insertOrIgnore($rows);
                }
            });
    }

    public function down()
    {
        Schema::dropIfExists('user_application_access');
    }
}
