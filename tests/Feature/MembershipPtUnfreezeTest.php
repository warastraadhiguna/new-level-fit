<?php

namespace Tests\Feature;

use App\Http\Controllers\Member\MemberRegistrationController;
use App\Http\Controllers\Trainer\TrainerSessionController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MembershipPtUnfreezeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTables();
        Carbon::setTestNow(Carbon::parse('2026-09-10 12:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stopping_membership_freeze_also_stops_linked_and_legacy_pt_freezes(): void
    {
        $memberRegistrationId = DB::table('member_registrations')->insertGetId([
            'member_id' => 10,
            'created_at' => '2026-08-01 00:00:00',
            'updated_at' => '2026-08-01 00:00:00',
        ]);
        $currentLeaveDayId = DB::table('leave_days')->insertGetId([
            'member_registration_id' => $memberRegistrationId,
            'submission_date' => '2026-09-01 00:00:00',
            'price' => 100000,
            'days' => 30,
        ]);
        $futureLeaveDayId = DB::table('leave_days')->insertGetId([
            'member_registration_id' => $memberRegistrationId,
            'leave_day_continue_id' => $currentLeaveDayId,
            'submission_date' => '2026-10-01 00:00:00',
            'price' => 100000,
            'days' => 30,
        ]);
        $trainerSessionId = DB::table('trainer_sessions')->insertGetId(['member_id' => 10]);

        DB::table('pt_leave_days')->insert([
            [
                'trainer_session_id' => $trainerSessionId,
                'member_leave_day_id' => null,
                'submission_date' => '2026-09-01 00:00:00',
                'price' => 0,
                'days' => 30,
            ],
            [
                'trainer_session_id' => $trainerSessionId,
                'member_leave_day_id' => $futureLeaveDayId,
                'submission_date' => '2026-10-01 00:00:00',
                'price' => 0,
                'days' => 30,
            ],
            [
                'trainer_session_id' => $trainerSessionId,
                'member_leave_day_id' => null,
                'submission_date' => '2026-09-01 00:00:00',
                'price' => 50000,
                'days' => 30,
            ],
        ]);

        $request = Request::create('/stopLeaveDays', 'PUT', [
            'member_registration_id' => $memberRegistrationId,
        ]);
        $this->app->instance('request', $request);

        app(MemberRegistrationController::class)->stopLeaveDays();

        $this->assertDatabaseHas('leave_days', [
            'id' => $currentLeaveDayId,
            'days' => 8,
        ]);
        $this->assertDatabaseMissing('leave_days', ['id' => $futureLeaveDayId]);
        $this->assertDatabaseHas('pt_leave_days', [
            'trainer_session_id' => $trainerSessionId,
            'member_leave_day_id' => $currentLeaveDayId,
            'submission_date' => '2026-09-01 00:00:00',
            'days' => 8,
        ]);
        $this->assertDatabaseMissing('pt_leave_days', [
            'member_leave_day_id' => $futureLeaveDayId,
        ]);
        $this->assertDatabaseHas('pt_leave_days', [
            'trainer_session_id' => $trainerSessionId,
            'member_leave_day_id' => null,
            'submission_date' => '2026-09-01 00:00:00',
            'price' => 50000,
            'days' => 30,
        ]);
    }

    public function test_stopping_manual_pt_freeze_shortens_current_freeze_and_removes_future_manual_freeze(): void
    {
        $trainerSessionId = DB::table('trainer_sessions')->insertGetId(['member_id' => 10]);
        $currentFreezeId = DB::table('pt_leave_days')->insertGetId([
            'trainer_session_id' => $trainerSessionId,
            'member_leave_day_id' => null,
            'submission_date' => '2026-09-01 00:00:00',
            'price' => 50000,
            'days' => 30,
        ]);
        $futureFreezeId = DB::table('pt_leave_days')->insertGetId([
            'trainer_session_id' => $trainerSessionId,
            'member_leave_day_id' => null,
            'submission_date' => '2026-10-01 00:00:00',
            'price' => 50000,
            'days' => 30,
        ]);

        $request = Request::create('/trainer-session/' . $trainerSessionId . '/unfreeze', 'PUT');
        $this->app->instance('request', $request);

        app(TrainerSessionController::class)->unfreeze((string) $trainerSessionId);

        $this->assertDatabaseHas('pt_leave_days', [
            'id' => $currentFreezeId,
            'days' => 8,
        ]);
        $this->assertDatabaseMissing('pt_leave_days', ['id' => $futureFreezeId]);
    }

    public function test_linked_membership_freeze_cannot_be_stopped_from_pt(): void
    {
        $memberRegistrationId = DB::table('member_registrations')->insertGetId([
            'member_id' => 10,
            'created_at' => '2026-08-01 00:00:00',
            'updated_at' => '2026-08-01 00:00:00',
        ]);
        $leaveDayId = DB::table('leave_days')->insertGetId([
            'member_registration_id' => $memberRegistrationId,
            'submission_date' => '2026-09-01 00:00:00',
            'price' => 100000,
            'days' => 30,
        ]);
        $trainerSessionId = DB::table('trainer_sessions')->insertGetId(['member_id' => 10]);
        $ptFreezeId = DB::table('pt_leave_days')->insertGetId([
            'trainer_session_id' => $trainerSessionId,
            'member_leave_day_id' => $leaveDayId,
            'submission_date' => '2026-09-01 00:00:00',
            'price' => 0,
            'days' => 30,
        ]);

        $request = Request::create('/trainer-session/' . $trainerSessionId . '/unfreeze', 'PUT');
        $this->app->instance('request', $request);

        app(TrainerSessionController::class)->unfreeze((string) $trainerSessionId);

        $this->assertDatabaseHas('pt_leave_days', [
            'id' => $ptFreezeId,
            'member_leave_day_id' => $leaveDayId,
            'days' => 30,
        ]);
    }

    private function createTables(): void
    {
        Schema::create('member_registrations', function (Blueprint $table) {
            $table->integer('days')->default(30);
            $table->increments('id');
            $table->integer('member_id');
            $table->timestamps();
        });
        Schema::create('leave_days', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('leave_day_continue_id')->nullable();
            $table->integer('member_registration_id');
            $table->dateTime('submission_date')->nullable();
            $table->integer('price');
            $table->integer('days');
        });
        Schema::create('trainer_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status')->nullable();
        });
        Schema::create('trainer_sessions', function (Blueprint $table) {
            $table->integer('trainer_package_id')->nullable();
            $table->increments('id');
            $table->integer('member_id');
        });
        Schema::create('pt_leave_days', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('trainer_session_id');
            $table->integer('member_leave_day_id')->nullable();
            $table->dateTime('submission_date')->nullable();
            $table->integer('price');
            $table->integer('days');
        });
    }
}
