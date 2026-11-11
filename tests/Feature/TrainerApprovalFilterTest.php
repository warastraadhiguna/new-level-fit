<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class TrainerApprovalFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-11 12:00:00', config('app.timezone')));
        Schema::create('branch_stores', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->boolean('trainer_approval_enabled')->default(true);
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->integer('branch_store_id'); $t->string('full_name'); $t->string('role');
            $t->string('email'); $t->string('password'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('user_application_access', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('application_code'); $t->boolean('is_active'); $t->timestamps();
        });
        Schema::create('members', function (Blueprint $t) {
            $t->id(); $t->string('full_name'); $t->string('member_code'); $t->softDeletes();
        });
        foreach (['personal_trainers','class_instructors'] as $table) Schema::create($table, function (Blueprint $t) {
            $t->id(); $t->string('full_name'); $t->softDeletes();
        });
        Schema::create('trainer_packages', function (Blueprint $t) {
            $t->id(); $t->string('package_name'); $t->string('status')->nullable();
            $t->integer('number_of_session')->default(2); $t->softDeletes();
        });
        Schema::create('trainer_sessions', function (Blueprint $t) {
            $t->id(); $t->integer('member_id'); $t->integer('trainer_id')->nullable()->default(1);
            $t->integer('trainer_package_id')->nullable()->default(1); $t->integer('branch_store_id')->default(1);
            $t->integer('user_id')->default(1); $t->dateTime('start_date')->nullable();
            $t->integer('days')->default(30); $t->integer('number_of_session')->nullable()->default(2);
            $t->integer('package_price')->default(100); $t->integer('admin_price')->default(20);
            $t->integer('discount_amount')->default(10); $t->boolean('is_approved')->default(false);
            $t->boolean('is_pt_free')->default(false); $t->text('description')->default('Needs review'); $t->timestamps();
        });
        Schema::create('trainer_session_payments', function (Blueprint $t) {
            $t->id(); $t->integer('trainer_session_id'); $t->integer('value');
        });
        Schema::create('check_in_trainer_sessions', function (Blueprint $t) {
            $t->id(); $t->integer('trainer_session_id'); $t->dateTime('check_in_time'); $t->dateTime('check_out_time')->nullable();
        });
        Schema::create('pt_leave_days', function (Blueprint $t) {
            $t->id(); $t->integer('trainer_session_id'); $t->dateTime('submission_date')->nullable(); $t->integer('days');
        });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'One'],['id'=>2,'name'=>'Two']]);
        DB::table('personal_trainers')->insert(['id'=>1,'full_name'=>'Trainer']);
        DB::table('trainer_packages')->insert([
            ['id'=>1,'package_name'=>'PT','status'=>null], ['id'=>2,'package_name'=>'LGT','status'=>'LGT'],
        ]);
        $this->actingAs(User::create(['branch_store_id'=>1,'full_name'=>'Owner','role'=>'OWNER','email'=>'owner@example.com','password'=>'test']));
        DB::table('user_application_access')->insert(['user_id'=>1,'application_code'=>'management','is_active'=>true]);
        $this->seedSession(1, '2026-11-01 00:00:00', 110);
        $this->seedSession(2, '2026-11-20 00:00:00', 0);
        $this->seedSession(3, '2026-09-01 00:00:00', 50);
        $this->seedSession(4, '2026-10-01 00:00:00', 50);
        DB::table('pt_leave_days')->insert(['trainer_session_id'=>4,'submission_date'=>'2026-10-10','days'=>20]);
        $this->seedSession(5, null, 0);
        $this->seedSession(6, '2026-11-01 00:00:00', 110, ['trainer_id'=>null]);
        $this->seedSession(7, '2026-11-01 00:00:00', 110);
        foreach ([1,2] as $i) DB::table('check_in_trainer_sessions')->insert([
            'trainer_session_id'=>7,'check_in_time'=>'2026-11-02 10:00:00','check_out_time'=>'2026-11-02 11:00:00',
        ]);
        $this->seedSession(8, '2026-11-01 00:00:00', 0, ['discount_amount'=>200]);
        $this->seedSession(9, '2026-11-11 12:00:00', 110, ['number_of_session'=>0]); // Legacy package fallback.
        $this->seedSession(10, '2026-10-12 12:00:00', 110, ['number_of_session'=>null]); // Expiry boundary.
        $this->seedSession(11, '2026-11-01 00:00:00', 110, ['is_approved'=>true]);
        $this->seedSession(12, '2026-11-01 00:00:00', 110, ['branch_store_id'=>2]);
        $this->seedSession(13, '2026-11-01 00:00:00', 110, ['is_pt_free'=>true]);
        $this->seedSession(14, '2026-11-01 00:00:00', 110, ['trainer_package_id'=>2]);
        $this->seedSession(15, '2026-11-01 00:00:00', 110, ['trainer_package_id'=>null]);
        DB::table('check_in_trainer_sessions')->insert([
            'trainer_session_id'=>1,'check_in_time'=>'2026-11-11 10:00:00','check_out_time'=>null,
        ]); // Running check-in does not consume a completed session.
    }

    private function seedSession(int $id, ?string $start, int $paid, array $extra=[]): void
    {
        DB::table('members')->insert(['id'=>$id,'full_name'=>'Member '.$id,'member_code'=>'M'.$id]);
        DB::table('trainer_sessions')->insert(array_merge([
            'id'=>$id,'member_id'=>$id,'start_date'=>$start,'created_at'=>now(),'updated_at'=>now(),
        ], $extra));
        if ($paid) DB::table('trainer_session_payments')->insert([
            ['trainer_session_id'=>$id,'value'=>20], ['trainer_session_id'=>$id,'value'=>$paid-20],
        ]);
    }

    public static function filters(): array
    {
        return [
            'all'=>['', [1,2,3,4,5,6,7,8,9,10,15]],
            'active'=>['status=active', [1,4,8,9,10,15]],
            'pending including missing date and trainer'=>['status=pending', [2,5,6]],
            'expired including exhausted sessions'=>['status=expired', [3,7]],
            'unpaid'=>['payment_status=unpaid', [2,3,4,5]],
            'paid'=>['payment_status=paid', [1,6,7,8,9,10,15]],
            'combined'=>['status=active&payment_status=unpaid', [4]],
            'combined with search'=>['status=pending&payment_status=paid&search=Member+6', [6]],
            'invalid defaults'=>['status=invalid&payment_status=invalid', [1,2,3,4,5,6,7,8,9,10,15]],
        ];
    }

    /** @dataProvider filters */
    public function test_filters_match_unapproved_pt_sessions(string $query, array $expected): void
    {
        $response=$this->get('/trainer-approval?per_page=25&'.$query)->assertOk();
        $this->assertSame($expected, $response->viewData('trainerSessions')->pluck('id')->sort()->values()->all());
    }

    public function test_filters_persist_in_sort_and_page_links_and_status_labels_match(): void
    {
        $response=$this->get('/trainer-approval?status=active&payment_status=paid')->assertOk()
            ->assertSee('name="status"', false)->assertSee('name="payment_status"', false)
            ->assertSee('status=active&amp;payment_status=paid', false);
        $page=$response->viewData('trainerSessions');
        $this->assertStringContainsString('status=active', $page->url(2));
        $this->assertStringContainsString('payment_status=paid', $page->url(2));
        $this->get('/trainer-approval?status=expired')->assertOk()
            ->assertSee('badge-danger badge-lg">Expired', false)->assertSee('badge-danger badge-lg">Sesi Habis', false);
        $this->get('/trainer-approval?status=pending')->assertOk()->assertSee('badge-info badge-lg">Pending', false);
    }

    public function test_combined_filters_apply_before_pagination(): void
    {
        for ($id=16; $id<=27; $id++) $this->seedSession($id, '2026-11-01 00:00:00', 50);
        $page=$this->get('/trainer-approval?status=active&payment_status=unpaid&per_page=10&page=2')
            ->assertOk()->viewData('trainerSessions');
        $this->assertSame(13, $page->total());
        $this->assertSame([17,16,4], $page->pluck('id')->all());
    }
}
