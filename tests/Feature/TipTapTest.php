<?php

namespace Tests\Feature;

use App\Models\Member\Member;
use App\Models\User;
use App\Services\TipTapService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TipTapTest extends TestCase
{
    private $service;
    private $owner;
    private $tables = [
        'members', 'member_registrations', 'trainer_sessions', 'class_details', 'trainers',
        'member_registration_payments', 'member_registration_installments', 'check_in_members',
        'leave_days', 'trainer_session_payments', 'check_in_trainer_sessions', 'pt_leave_days',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys = ON');
        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name');
            $table->string('member_code')->unique();
            $table->string('photos')->nullable();
            $table->string('small_photos')->nullable();
        });
        $relations = [
            'member_registrations' => ['member_id' => 'members'],
            'trainer_sessions' => ['member_id' => 'members'],
            'class_details' => ['member_id' => 'members'],
            'trainers' => ['member_id' => 'member_registrations'],
            'member_registration_payments' => ['member_registration_id' => 'member_registrations'],
            'member_registration_installments' => ['member_registration_id' => 'member_registrations'],
            'check_in_members' => ['member_registration_id' => 'member_registrations'],
            'leave_days' => ['member_registration_id' => 'member_registrations'],
            'trainer_session_payments' => ['trainer_session_id' => 'trainer_sessions'],
            'check_in_trainer_sessions' => ['trainer_session_id' => 'trainer_sessions'],
            'pt_leave_days' => ['trainer_session_id' => 'trainer_sessions', 'member_leave_day_id' => 'leave_days'],
        ];
        foreach ($relations as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($name, $columns) {
                $table->increments('id');
                if ($name === 'member_registrations') $table->integer('days')->default(30);
                if ($name === 'trainer_sessions') $table->integer('trainer_package_id')->default(1);
                foreach ($columns as $column => $parent) {
                    $table->unsignedInteger($column)->nullable();
                    $table->foreign($column)->references('id')->on($parent);
                }
                if (strpos($name, 'payments') !== false) {
                    $table->integer('value');
                }
            });
        }
        Schema::create('trainer_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status')->nullable();
            $table->string('package_name')->default('Demo');
        });
        $migration = require database_path('migrations/2026_09_19_000002_create_tip_tap_trash.php');
        $migration->up();
        $rename = require database_path('migrations/2026_09_19_000003_rename_tip_tap_tables.php');
        $rename->up();
        $this->service = app(TipTapService::class);
        $this->owner = new User(['role' => 'OWNER']);
        $this->owner->id = 1;
        foreach ([1, 2] as $id) {
            DB::table('members')->insert(['id' => $id, 'full_name' => 'Member ' . $id, 'member_code' => 'M' . $id]);
            foreach ($relations as $table => $columns) {
                $row = ['id' => $id];
                foreach ($columns as $column => $parent) {
                    $row[$column] = $id;
                }
                if (strpos($table, 'payments') !== false) {
                    $row['value'] = $id * 100000;
                }
                DB::table($table)->insert($row);
            }
        }
    }

    public function test_member_disappears_from_raw_queries_models_and_revenue_and_restores_exact_rows(): void
    {
        $before = $this->snapshot();
        $this->service->trash($this->owner, 'member', 1);
        foreach ($this->tables as $table) {
            $this->assertDatabaseMissing($table, ['id' => 1]);
            $this->assertDatabaseHas($table, ['id' => 2]);
        }
        $this->assertNull(Member::find(1));
        $this->assertSame(200000, (int) DB::table('member_registration_payments')->sum('value'));
        $this->assertSame(200000, (int) DB::table('trainer_session_payments')->sum('value'));
        $this->assertStringNotContainsString('Member 1', DB::table('trashes')->value('payload'));
        $this->service->restore($this->owner, $this->trashId('member'));
        $this->assertEquals($before, $this->snapshot());
        $this->assertDatabaseCount('trashes', 0);
    }

    public function test_membership_deletion_preserves_member_and_pt_but_removes_linked_freeze(): void
    {
        $before = $this->snapshot();
        $this->service->trash($this->owner, 'membership', 1, 1);
        $this->assertDatabaseHas('members', ['id' => 1]);
        $this->assertDatabaseHas('trainer_sessions', ['id' => 1]);
        $this->assertDatabaseHas('trainer_session_payments', ['id' => 1]);
        $this->assertDatabaseMissing('member_registration_payments', ['id' => 1]);
        $this->assertDatabaseMissing('pt_leave_days', ['id' => 1]);
        $this->service->restore($this->owner, $this->trashId('membership'));
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_pt_deletion_preserves_membership_and_can_be_restored(): void
    {
        $before = $this->snapshot();
        $this->service->trash($this->owner, 'pt', 1, 1);
        $this->assertDatabaseMissing('trainer_sessions', ['id' => 1]);
        $this->assertDatabaseMissing('trainer_session_payments', ['id' => 1]);
        $this->assertDatabaseHas('member_registrations', ['id' => 1]);
        $this->assertDatabaseHas('member_registration_payments', ['id' => 1]);
        $this->service->restore($this->owner, $this->trashId('pt'));
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_restoring_member_does_not_restore_previously_trashed_membership(): void
    {
        $this->service->trash($this->owner, 'membership', 1);
        $this->service->trash($this->owner, 'member', 1);
        try {
            $this->service->restore($this->owner, $this->trashId('membership'));
            $this->fail('Cannot restore a child before its member.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('trashes', 2);
        }
        $this->service->restore($this->owner, $this->trashId('member'));
        $this->assertDatabaseMissing('member_registrations', ['id' => 1]);
        $this->service->restore($this->owner, $this->trashId('membership'));
        $this->assertDatabaseHas('member_registrations', ['id' => 1]);
    }

    public function test_permanent_member_deletion_removes_nested_archives_and_photos(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assets/member/photo.jpg', 'photo');
        DB::table('members')->where('id', 1)->update(['photos' => 'assets/member/photo.jpg']);
        $this->service->trash($this->owner, 'pt', 1);
        $this->service->trash($this->owner, 'member', 1);
        Storage::disk('public')->assertExists('assets/member/photo.jpg');
        $this->service->purge($this->owner, $this->trashId('member'));
        $this->assertDatabaseCount('trashes', 0);
        Storage::disk('public')->assertMissing('assets/member/photo.jpg');
        foreach ($this->tables as $table) {
            $this->assertDatabaseMissing($table, ['id' => 1]);
            $this->assertDatabaseHas($table, ['id' => 2]);
        }
    }

    public function test_permanent_pt_deletion_drops_linked_freeze_from_separate_membership_archive(): void
    {
        $this->service->trash($this->owner, 'membership', 1);
        $this->service->trash($this->owner, 'pt', 1);
        $this->service->purge($this->owner, $this->trashId('pt'));
        $this->service->restore($this->owner, $this->trashId('membership'));
        $this->assertDatabaseHas('member_registrations', ['id' => 1]);
        $this->assertDatabaseMissing('trainer_sessions', ['id' => 1]);
        $this->assertDatabaseMissing('pt_leave_days', ['id' => 1]);
    }

    public function test_permanent_membership_deletion_drops_linked_freeze_from_separate_pt_archive(): void
    {
        $this->service->trash($this->owner, 'pt', 1);
        $this->service->trash($this->owner, 'membership', 1);
        $this->service->purge($this->owner, $this->trashId('membership'));
        $this->service->restore($this->owner, $this->trashId('pt'));
        $this->assertDatabaseHas('trainer_sessions', ['id' => 1]);
        $this->assertDatabaseMissing('member_registrations', ['id' => 1]);
        $this->assertDatabaseMissing('pt_leave_days', ['id' => 1]);
    }

    public function test_conflicting_restore_rolls_back_without_losing_archive(): void
    {
        $this->service->trash($this->owner, 'member', 1);
        DB::table('members')->insert(['id' => 3, 'full_name' => 'New', 'member_code' => 'M1']);
        try {
            $this->service->restore($this->owner, $this->trashId('member'));
            $this->fail('Unique member code conflict must fail.');
        } catch (QueryException $exception) {
            $this->assertDatabaseCount('trashes', 1);
            $this->assertDatabaseMissing('members', ['id' => 1]);
            $this->assertDatabaseHas('members', ['id' => 3]);
        }
    }

    public function test_failed_delete_rolls_back_archive_and_all_children(): void
    {
        Schema::create('external_references', function (Blueprint $table) {
            $table->integer('member_id');
            $table->foreign('member_id')->references('id')->on('members');
        });
        DB::table('external_references')->insert(['member_id' => 1]);
        $before = $this->snapshot();
        try {
            $this->service->trash($this->owner, 'member', 1);
            $this->fail('Unmapped FK must fail atomically.');
        } catch (QueryException $exception) {
            $this->assertEquals($before, $this->snapshot());
            $this->assertDatabaseCount('trashes', 0);
        }
    }

    public function test_cross_member_request_is_rejected(): void
    {
        $this->actingAs($this->owner)->delete(route('tip-tap.trash', [2, 'membership', 1]))->assertNotFound();
        $this->assertDatabaseHas('member_registrations', ['id' => 1]);
    }

    public function test_owner_can_complete_the_http_delete_restore_and_purge_flow(): void
    {
        $this->actingAs($this->owner);
        $this->delete(route('tip-tap.trash', [1, 'pt', 1]))->assertRedirect();
        $id = $this->trashId('pt');
        $this->view('admin.tip-tap.index', [
            'entries' => DB::table('trashes')->paginate(20),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->assertSee('PT registration')->assertSee('HAPUS PERMANEN');
        $this->post(route('tip-tap.restore', $id))->assertRedirect();
        $this->assertDatabaseHas('trainer_sessions', ['id' => 1]);
        $this->delete(route('members.destroy', 1))->assertRedirect(route('members.index'));
        $id = $this->trashId('member');
        $this->delete(route('tip-tap.purge', $id), ['confirmation' => 'HAPUS PERMANEN'])->assertRedirect();
        $this->assertDatabaseCount('trashes', 0);
        $this->post(route('tip-tap.restore', $id))->assertNotFound();
        $this->assertDatabaseMissing('members', ['id' => 1]);
    }

    public function test_guests_cannot_access_tip_tap(): void
    {
        $this->get(route('tip-tap.index'))->assertRedirect(route('login'));
        $this->post(route('tip-tap.restore', 1))->assertRedirect(route('login'));
        $this->delete(route('tip-tap.trash', [1, 'membership', 1]))->assertRedirect(route('login'));
        $this->delete(route('tip-tap.purge', 1))->assertRedirect(route('login'));
    }

    public function test_all_tip_tap_endpoints_and_legacy_delete_routes_reject_non_owner(): void
    {
        foreach (['ADMIN', 'CS', 'CSPOS', 'FC', 'PT'] as $role) {
            $user = new User(['role' => $role]);
            $user->id = 2;
            $this->actingAs($user);
            $this->get(route('tip-tap.index'))->assertForbidden();
            $this->delete(route('tip-tap.trash', [1, 'membership', 1]))->assertForbidden();
            $this->post(route('tip-tap.restore', 1))->assertForbidden();
            $this->delete(route('tip-tap.purge', 1), ['confirmation' => 'HAPUS PERMANEN'])->assertForbidden();
            $this->delete(route('member.destroy', 1))->assertForbidden();
            $this->delete(route('members.destroy', 1))->assertForbidden();
            $this->delete(route('member-active.destroy', 1))->assertForbidden();
            $this->delete(route('trainer-session.destroy', 1))->assertForbidden();
        }
        $this->assertDatabaseCount('trashes', 0);
    }

    public function test_purge_requires_confirmation_and_a_trashed_entry(): void
    {
        $this->actingAs($this->owner)->delete(route('tip-tap.purge', 1), ['confirmation' => 'HAPUS PERMANEN'])->assertNotFound();
        $this->service->trash($this->owner, 'membership', 1);
        $this->actingAs($this->owner)->deleteJson(route('tip-tap.purge', $this->trashId('membership')))->assertUnprocessable();
        $this->assertDatabaseCount('trashes', 1);
    }

    public function test_archived_member_code_cannot_be_reused_until_purged(): void
    {
        $this->service->trash($this->owner, 'member', 1);
        try {
            Member::create(['full_name' => 'New member', 'member_code' => 'M1']);
            $this->fail('Trashed member code must remain reserved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('member_code', $exception->errors());
        }
        $this->service->purge($this->owner, $this->trashId('member'));
        $new = Member::create(['full_name' => 'New member', 'member_code' => 'M1']);
        $this->assertTrue($new->exists);
    }

    public function test_actual_revenue_report_excludes_trash_and_restores_totals(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', function (...$parts) { return implode('', $parts); });
        Schema::table('members', function (Blueprint $table) {
            $table->integer('branch_store_id')->default(1);
            $table->string('email')->nullable();
        });
        Schema::table('member_registrations', function (Blueprint $table) {
            $table->integer('member_package_id')->default(1);
        });
        Schema::table('trainer_sessions', function (Blueprint $table) {
            $table->integer('branch_store_id')->default(1);
            $table->boolean('is_pt_free')->default(false);
        });
        foreach (['member_registration_payments', 'trainer_session_payments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('branch_store_id')->default(1);
                $table->integer('method_payment_id')->default(1);
                $table->integer('user_id')->default(1);
                $table->dateTime('created_at')->default('2026-09-19 12:00:00');
            });
        }
        foreach (['member_packages', 'trainer_packages'] as $name) {
            if (!Schema::hasTable($name)) Schema::create($name, function (Blueprint $table) {
                $table->increments('id');
                $table->string('package_name');
                $table->string('status')->default('PT');
            });
            DB::table($name)->insert(['id' => 1, 'package_name' => 'Demo']);
        }
        Schema::create('method_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('branch_store_id');
            $table->string('full_name');
        });
        DB::table('users')->insert(['id' => 1, 'branch_store_id' => 1, 'full_name' => 'Owner']);
        DB::table('method_payments')->insert(['id' => 1, 'name' => 'Cash']);
        $total = function () {
            return (int) app(\App\Services\RevenueReportService::class)
                ->query(1, '2026-09-01', '2026-09-30', false)->get()->sum('amount');
        };
        DB::table('member_registrations')->insert(['id' => 3, 'member_id' => 1, 'days' => 1]);
        DB::table('member_registration_payments')->insert(['id' => 3, 'member_registration_id' => 3, 'value' => 900000]);
        DB::table('trainer_packages')->insert(['id' => 2, 'package_name' => 'Old group training', 'status' => 'LGT']);
        DB::table('trainer_sessions')->insert(['id' => 3, 'member_id' => 1, 'trainer_package_id' => 2]);
        DB::table('trainer_session_payments')->insert(['id' => 3, 'trainer_session_id' => 3, 'value' => 800000]);
        $this->assertSame(600000, $total());
        $this->service->trash($this->owner, 'membership', 1);
        $this->assertSame(500000, $total());
        $this->service->trash($this->owner, 'member', 1);
        $this->assertSame(400000, $total());
        $this->service->restore($this->owner, $this->trashId('member'));
        $this->assertSame(500000, $total());
        $this->service->restore($this->owner, $this->trashId('membership'));
        $this->assertSame(600000, $total());
    }

    private function trashId(string $kind): int
    {
        return (int) DB::table('trashes')->where('kind', $kind)->value('id');
    }

    private function snapshot(): array
    {
        $rows = [];
        foreach ($this->tables as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }
        return $rows;
    }
}
