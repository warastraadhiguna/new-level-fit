<?php

namespace Tests\Feature;

use App\Models\Member\CheckInMember;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberRegistration;
use App\Models\Member\MemberRegistrationPayment;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerSession;
use App\Models\Trainer\TrainerSessionPayment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RemovedGymFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        foreach (['member_packages', 'trainer_packages'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('days')->default(30);
                $table->string('status')->nullable();
                $table->softDeletes();
            });
        }
        Schema::create('member_registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('days');
        });
        Schema::create('trainer_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('trainer_package_id');
            $table->boolean('is_pt_free')->default(false);
        });
        foreach (['check_in_members', 'member_registration_payments', 'check_in_trainer_sessions', 'trainer_session_payments'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->increments('id');
                $table->integer(strpos($name, 'trainer') !== false ? 'trainer_session_id' : 'member_registration_id');
            });
        }
        DB::table('member_packages')->insert([['id' => 1, 'days' => 30], ['id' => 2, 'days' => 1]]);
        DB::table('member_registrations')->insert([['id' => 1, 'days' => 30], ['id' => 2, 'days' => 1]]);
        DB::table('trainer_packages')->insert([['id' => 1, 'status' => null], ['id' => 2, 'status' => 'LGT']]);
        DB::table('trainer_sessions')->insert([
            ['id' => 1, 'trainer_package_id' => 1, 'is_pt_free' => false],
            ['id' => 2, 'trainer_package_id' => 2, 'is_pt_free' => false],
            ['id' => 3, 'trainer_package_id' => 1, 'is_pt_free' => true],
        ]);
        foreach (['check_in_members', 'member_registration_payments', 'check_in_trainer_sessions', 'trainer_session_payments'] as $name) {
            $key = strpos($name, 'trainer') !== false ? 'trainer_session_id' : 'member_registration_id';
            DB::table($name)->insert([['id' => 1, $key => 1], ['id' => 2, $key => 2]]);
        }
    }

    public function test_disabled_packages_registrations_and_their_children_are_not_visible(): void
    {
        foreach ([MemberPackage::class, TrainerPackage::class, MemberRegistration::class,
            CheckInMember::class, MemberRegistrationPayment::class,
            CheckInTrainerSession::class, TrainerSessionPayment::class] as $model) {
            $this->assertEquals([1], $model::pluck('id')->all(), $model);
            $this->assertNull($model::find(2));
        }
        $this->assertEquals([1, 3], TrainerSession::orderBy('id')->pluck('id')->all());
        $this->assertNull(TrainerSession::find(2));
        $this->assertDatabaseHas('trainer_sessions', ['id' => 2]);
        $this->assertDatabaseHas('member_registrations', ['id' => 2]);
    }

    public function test_old_feature_urls_are_removed_even_for_owner(): void
    {
        $this->actingAs(new User(['role' => 'OWNER']));
        foreach (['/lgt', '/leave-days-lgt/1', '/lgt-second-check-in/1', '/one-day-visit',
            '/1-day-visit-lead', '/member-one-visit-detail/1', '/one-visit-report', '/openMembers'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->post('/lgt-check-in')->assertNotFound();
        $this->postJson('/member-second-store', ['status' => 'one_day_visit'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_package_forms_reject_one_day_duration_and_lgt_flag(): void
    {
        foreach ([\App\Http\Requests\MemberPackageStoreRequest::class, \App\Http\Requests\MemberPackageUpdateRequest::class] as $request) {
            $rules = (new $request())->rules();
            $this->assertTrue(Validator::make(['days' => 1], ['days' => $rules['days']])->fails());
            $this->assertFalse(Validator::make(['days' => 30], ['days' => $rules['days']])->fails());
        }
        foreach ([\App\Http\Requests\TrainerPackageStoreRequest::class, \App\Http\Requests\TrainerPackageUpdateRequest::class] as $request) {
            $rules = (new $request())->rules();
            $this->assertTrue(Validator::make(['status' => 'LGT'], ['status' => $rules['status']])->fails());
            $this->assertFalse(Validator::make([], ['status' => $rules['status']])->fails());
        }
    }

    public function test_legacy_membership_history_sql_excludes_one_day_registrations(): void
    {
        foreach ([
            fn () => MemberRegistration::history('', '', '2026-09-01', '2026-09-30'),
            fn () => MemberRegistration::historyById('', 2),
        ] as $query) {
            $queries = DB::connection()->pretend($query);
            $this->assertStringContainsString('mbr_reg.days > 1', $queries[0]['query']);
        }
    }
}
