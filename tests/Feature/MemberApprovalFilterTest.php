<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class MemberApprovalFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-11 12:00:00', config('app.timezone')));
        Schema::create('branch_stores', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->boolean('member_approval_enabled')->default(true);
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->integer('branch_store_id'); $t->string('full_name'); $t->string('role');
            $t->string('email'); $t->string('password'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('user_application_access', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('application_code'); $t->boolean('is_active'); $t->timestamps();
        });
        Schema::create('members', function (Blueprint $t) {
            $t->id(); $t->integer('branch_store_id'); $t->string('full_name'); $t->string('member_code'); $t->softDeletes();
        });
        Schema::create('member_packages', function (Blueprint $t) {
            $t->id(); $t->string('package_name'); $t->boolean('is_all_club')->default(false); $t->softDeletes();
        });
        Schema::create('method_payments', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('member_registrations', function (Blueprint $t) {
            $t->id(); $t->integer('member_id'); $t->integer('member_package_id')->default(1);
            $t->integer('method_payment_id')->default(1); $t->integer('user_id')->default(1);
            $t->dateTime('start_date'); $t->integer('days')->default(30);
            $t->integer('package_price')->default(100); $t->integer('admin_price')->default(20);
            $t->integer('discount_amount')->default(10); $t->boolean('is_approved')->default(false);
            $t->text('description')->default('Needs review'); $t->timestamps();
        });
        Schema::create('member_registration_payments', function (Blueprint $t) {
            $t->id(); $t->integer('member_registration_id'); $t->integer('value');
        });
        Schema::create('check_in_members', function (Blueprint $t) {
            $t->id(); $t->integer('member_registration_id'); $t->dateTime('check_in_time'); $t->dateTime('check_out_time')->nullable();
        });
        Schema::create('leave_days', function (Blueprint $t) {
            $t->id(); $t->integer('member_registration_id'); $t->dateTime('submission_date')->nullable();
            $t->integer('days'); $t->integer('leave_day_continue_id')->nullable();
        });
        foreach (['personal_trainers', 'class_instructors'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->softDeletes(); });
        }
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'], ['id'=>2,'name'=>'Two']]);
        DB::table('member_packages')->insert([
            ['id'=>1,'package_name'=>'One Club','is_all_club'=>false],
            ['id'=>2,'package_name'=>'All Club','is_all_club'=>true],
        ]);
        DB::table('method_payments')->insert(['id'=>1,'name'=>'Cash']);
        $this->actingAs(User::create([
            'branch_store_id'=>1,'full_name'=>'Owner','role'=>'OWNER','email'=>'owner@example.com','password'=>'test',
        ]));
        DB::table('user_application_access')->insert(['user_id'=>1,'application_code'=>'management','is_active'=>true]);
        $this->registration(1, '2026-11-01 00:00:00', 110); // Active, paid in two payments.
        $this->registration(2, '2026-11-20 00:00:00', 0); // Pending, no payments.
        $this->registration(3, '2026-09-01 00:00:00', 50); // Expired, partially paid.
        $this->registration(4, '2026-10-01 00:00:00', 50); // Freeze extends expiry past today.
        DB::table('leave_days')->insert(['member_registration_id'=>4,'submission_date'=>'2026-10-10 00:00:00','days'=>20]);
        $this->registration(5, '2026-11-01 00:00:00', 0, ['discount_amount'=>200]); // Zero payable is paid.
        $this->registration(6, '2026-11-01 00:00:00', 110, ['is_approved'=>true]);
        $this->registration(7, '2026-11-01 00:00:00', 110, [], 2); // Foreign one-club excluded.
        $this->registration(8, '2026-11-01 00:00:00', 110, ['member_package_id'=>2], 2); // All-club included.
        $this->registration(9, '2026-11-11 12:00:00', 110); // Exactly at start is active.
        $this->registration(10, '2026-10-12 12:00:00', 110); // Exactly at expiry is active.
    }

    private function registration(int $id, string $start, int $paid, array $extra = [], int $branch = 1): void
    {
        DB::table('members')->insert(['id'=>$id,'branch_store_id'=>$branch,'full_name'=>'Member '.$id,'member_code'=>'M'.$id]);
        DB::table('member_registrations')->insert(array_merge([
            'id'=>$id,'member_id'=>$id,'start_date'=>$start,'created_at'=>now(),'updated_at'=>now(),
        ], $extra));
        if ($paid) DB::table('member_registration_payments')->insert([
            ['member_registration_id'=>$id,'value'=>20], ['member_registration_id'=>$id,'value'=>$paid-20],
        ]);
    }

    public static function filters(): array
    {
        return [
            'all' => ['', [1,2,3,4,5,8,9,10]],
            'active including freeze and boundaries' => ['status=active', [1,4,5,8,9,10]],
            'pending' => ['status=pending', [2]],
            'expired' => ['status=expired', [3]],
            'unpaid includes absent and partial payments' => ['payment_status=unpaid', [2,3,4]],
            'paid includes fully discounted membership' => ['payment_status=paid', [1,5,8,9,10]],
            'combined' => ['status=active&payment_status=unpaid', [4]],
            'search and combined' => ['status=active&payment_status=paid&search=Member+8', [8]],
            'invalid filters fall back to all' => ['status=invalid&payment_status=invalid', [1,2,3,4,5,8,9,10]],
        ];
    }

    /** @dataProvider filters */
    public function test_filters_select_matching_unapproved_memberships(string $query, array $expected): void
    {
        $response=$this->get('/member-approval?'.$query)->assertOk();
        $ids=$response->viewData('memberRegistrations')->pluck('id')->sort()->values()->all();
        $this->assertSame($expected, $ids);
    }

    public function test_filters_survive_sorting_and_pagination_and_status_labels_match_dates(): void
    {
        $response=$this->get('/member-approval?status=active&payment_status=paid&per_page=10')->assertOk()
            ->assertSee('name="status"', false)->assertSee('name="payment_status"', false);
        $response->assertSee('status=active&amp;payment_status=paid', false);
        $this->assertStringContainsString('status=active', $response->viewData('memberRegistrations')->url(2));
        $this->assertStringContainsString('payment_status=paid', $response->viewData('memberRegistrations')->url(2));
        $this->get('/member-approval?status=expired')->assertOk()->assertSee('badge-danger badge-lg">Expired', false);
        $this->get('/member-approval?status=pending')->assertOk()->assertSee('badge-info badge-lg">Pending', false);
    }

    public function test_combined_filters_are_applied_before_pagination(): void
    {
        for ($id=11; $id<=22; $id++) $this->registration($id, '2026-11-01 00:00:00', 50);
        $response=$this->get('/member-approval?status=active&payment_status=unpaid&per_page=10&page=2')->assertOk();
        $page=$response->viewData('memberRegistrations');
        $this->assertSame(13, $page->total());
        $this->assertCount(3, $page->items());
        $this->assertSame([12,11,4], $page->pluck('id')->all());
    }
}
