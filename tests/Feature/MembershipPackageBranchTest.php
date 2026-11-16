<?php
namespace Tests\Feature;

use App\Models\{User,BranchStore};
use App\Models\Member\{MemberRegistration,MemberRegistrationPayment};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;

class MembershipPackageBranchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-16 10:00:00','Asia/Jakarta'));
        Schema::create('branch_stores',function(Blueprint $t) {
            $t->id();$t->string('name');$t->boolean('member_approval_enabled')->default(true);
            $t->boolean('member_discount_enabled')->default(false);$t->boolean('is_payment_strict')->default(true);
            $t->boolean('pos_inventory_enabled')->default(false);$t->boolean('member_installment_enabled')->default(false);
        });
        Schema::create('users',function(Blueprint $t) {
            $t->id();$t->integer('branch_store_id');$t->string('full_name');$t->string('role');$t->string('email');$t->string('password');$t->timestamps();$t->softDeletes();
        });
        Schema::create('user_application_access',function(Blueprint $t) { $t->id();$t->integer('user_id');$t->string('application_code');$t->boolean('is_active');$t->timestamps(); });
        Schema::create('members',function(Blueprint $t) {
            $t->id();$t->integer('branch_store_id');$t->string('full_name');$t->string('nickname')->nullable();$t->string('status')->default('sell');
            $t->string('member_code');$t->string('card_number');$t->string('photos')->nullable();$t->date('born')->nullable();$t->timestamps();
        });
        Schema::create('member_packages',function(Blueprint $t) {
            $t->id();$t->integer('branch_store_id');$t->string('package_name');$t->boolean('is_all_club');$t->integer('days')->default(90);
            $t->integer('package_price')->default(900000);$t->integer('admin_price')->default(0);$t->boolean('is_installment_plan')->default(false);$t->timestamps();$t->softDeletes();
        });
        Schema::create('member_registrations',function(Blueprint $t) {
            $t->id();$t->integer('member_id');$t->integer('member_package_id');$t->integer('user_id');$t->integer('fc_id')->nullable();$t->integer('method_payment_id')->default(1);
            $t->integer('package_price')->default(900000);$t->integer('admin_price')->default(0);$t->integer('discount_amount')->default(0);$t->integer('days')->default(90);
            $t->dateTime('start_date');$t->integer('payment_deadline')->default(0);$t->boolean('is_installment_plan')->default(false);$t->boolean('is_approved')->default(false);
            $t->text('description')->nullable();$t->timestamps();
        });
        Schema::create('member_registration_payments',function(Blueprint $t) {
            $t->id();$t->integer('branch_store_id');$t->integer('member_registration_id');$t->integer('user_id');$t->integer('method_payment_id');$t->integer('value');$t->string('note');$t->timestamps();
        });
        Schema::create('member_registration_installments',function(Blueprint $t) { $t->id();$t->integer('member_registration_id'); });
        Schema::create('method_payments',function(Blueprint $t) { $t->id();$t->string('name'); });
        foreach (['personal_trainers','class_instructors'] as $table) Schema::create($table,function(Blueprint $t) { $t->id();$t->softDeletes(); });
        DB::table('branch_stores')->insert([['id'=>1,'name'=>'Supriyadi'],['id'=>2,'name'=>'Pamularsih'],['id'=>3,'name'=>'Candisari']]);
        DB::table('method_payments')->insert(['id'=>1,'name'=>'Cash']);
        DB::table('member_packages')->insert([
            ['id'=>1,'branch_store_id'=>1,'package_name'=>'Supriyadi All Club','is_all_club'=>1],
            ['id'=>2,'branch_store_id'=>2,'package_name'=>'Pamularsih One Club','is_all_club'=>0],
            ['id'=>3,'branch_store_id'=>3,'package_name'=>'Candisari One Club','is_all_club'=>0],
        ]);
        DB::table('members')->insert(['id'=>1,'branch_store_id'=>2,'full_name'=>'Maria fixture','member_code'=>'WSP-test','card_number'=>'CARD-test']);
        DB::table('member_registrations')->insert(['id'=>1,'member_id'=>1,'member_package_id'=>1,'user_id'=>1,'start_date'=>'2026-07-16','created_at'=>now(),'updated_at'=>now()]);
        $this->actor(3);
    }
    private function actor(int $branch): User
    {
        $user=User::firstOrCreate(['email'=>'owner-'.$branch.'@example.com'],['branch_store_id'=>$branch,'full_name'=>'Owner '.$branch,'role'=>'OWNER','password'=>'test']);
        DB::table('user_application_access')->updateOrInsert(['user_id'=>$user->id,'application_code'=>'management'],['is_active'=>true]);
        $this->actingAs($user);return $user;
    }
    private function payload(int $package=3): array
    {
        return ['member_package_id'=>$package,'start_date'=>'2026-11-15','method_payment_id'=>1,'first_payment'=>'900.000','description'=>'Renewal branch test'];
    }
    public function test_all_club_can_renew_into_foreign_one_club_without_moving_member_or_old_payment(): void
    {
        DB::table('member_registration_payments')->insert(['member_registration_id'=>1,'branch_store_id'=>1,'user_id'=>1,'method_payment_id'=>1,'value'=>900000,'note'=>'Old payment']);
        DB::table('member_packages')->where('id',1)->update(['deleted_at'=>now()]);
        $this->get('/renewal/1')->assertOk()->assertSee('Candisari One Club')->assertDontSee('value="2" data-price',false);
        $this->post('/renewal/store/1',$this->payload())->assertRedirect(route('member-active.index'))->assertSessionHasNoErrors();
        $new=MemberRegistration::latest('id')->first();$this->assertSame(3,$new->member_package_id);
        $this->assertDatabaseHas('members',['id'=>1,'branch_store_id'=>2,'member_code'=>'WSP-test']);
        $this->assertDatabaseHas('member_registrations',['id'=>1,'member_package_id'=>1]);
        $this->assertDatabaseHas('member_registration_payments',['member_registration_id'=>1,'branch_store_id'=>1,'value'=>900000,'note'=>'Old payment']);
        $this->assertDatabaseHas('member_registration_payments',['member_registration_id'=>$new->id,'branch_store_id'=>3,'value'=>900000]);
        $this->assertSame([$new->id],MemberRegistration::accessibleInBranch(3)->where('member_package_id',3)->pluck('id')->all());
        $this->assertSame([],MemberRegistration::accessibleInBranch(2)->where('member_package_id',3)->pluck('id')->all());
    }
    public function test_one_club_cannot_renew_cross_branch_but_create_membership_uses_same_member(): void
    {
        DB::table('member_registrations')->where('id',1)->update(['member_package_id'=>2]);
        $this->get('/renewal/1')->assertForbidden();
        $this->postJson('/renewal/store/1',$this->payload())->assertForbidden();
        $this->assertDatabaseCount('member_registrations',1);$this->assertDatabaseCount('member_registration_payments',0);
        $this->post('/members/1/store-membership',$this->payload())->assertSessionHasNoErrors()->assertRedirect(route('member-active.index'));
        $this->assertDatabaseHas('member_registrations',['member_id'=>1,'member_package_id'=>3]);
        $this->assertDatabaseCount('members',1);$this->assertDatabaseHas('members',['id'=>1,'branch_store_id'=>2]);
        $this->actor(2);$this->post('/renewal/store/1',$this->payload(2))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('member_registration_payments',['branch_store_id'=>2,'value'=>900000]);
    }
    public function test_forged_package_branch_is_rejected_for_renewal_and_new_membership(): void
    {
        $this->postJson('/renewal/store/1',$this->payload(1))->assertStatus(422)->assertJsonValidationErrors('member_package_id');
        $this->postJson('/renewal/store/1',$this->payload(2))->assertStatus(422)->assertJsonValidationErrors('member_package_id');
        $this->postJson('/members/1/store-membership',$this->payload(2))->assertStatus(422)->assertJsonValidationErrors('member_package_id');
        $this->assertDatabaseCount('member_registrations',1);$this->assertDatabaseCount('member_registration_payments',0);
    }
    public function test_registration_branch_scope_follows_package_including_deleted_packages(): void
    {
        DB::table('member_registrations')->insert(['id'=>2,'member_id'=>1,'member_package_id'=>3,'user_id'=>1,'start_date'=>'2026-11-15','created_at'=>now(),'updated_at'=>now()]);
        DB::table('member_packages')->where('id',3)->update(['deleted_at'=>now()]);
        $this->assertSame([1,2],MemberRegistration::accessibleInBranch(3)->orderBy('id')->pluck('id')->all());
        $this->assertSame([1],MemberRegistration::accessibleInBranch(2)->pluck('id')->all());
        $this->assertSame([1],MemberRegistration::accessibleInBranch(1)->pluck('id')->all());
    }
    public function test_legacy_list_queries_use_package_branch_and_label(): void
    {
        $queries=DB::connection()->pretend(function() {
            MemberRegistration::getActiveList('',1,'no',3);
            MemberRegistration::getActiveList('',1,'yes',3);
            MemberRegistration::getActiveListPaginated('',10,'updated_at_check_in','desc',3);
            MemberRegistration::getPendingList(1,3);
            MemberRegistration::history('',1,'2026-11-01','2026-11-30',3);
            MemberRegistration::expiredRegistrations('',3)->get();
        });
        $this->assertGreaterThanOrEqual(6,count($queries));
        foreach ($queries as $query) {
            $this->assertStringNotContainsString('mbr.branch_store_id=',$query['query']);
            $this->assertStringNotContainsString('mbr.branch_store_id = bs.id',$query['query']);
            $this->assertStringNotContainsString('bs.id = a.branch_store_id',$query['query']);
        }
    }

    public function test_card_checkin_finds_accessible_membership_even_when_another_club_is_first(): void
    {
        $controller=new \App\Http\Controllers\Member\MemberCheckInController();
        $filter=new \ReflectionMethod($controller,'accessibleMemberships');$filter->setAccessible(true);
        $foreign=(object)['id'=>10,'branch_store_id'=>3,'is_all_club'=>0,'member_package_branch_store_id'=>2];
        $candisari=(object)['id'=>11,'branch_store_id'=>2,'is_all_club'=>0,'member_package_branch_store_id'=>3];
        $allClub=(object)['id'=>12,'branch_store_id'=>1,'is_all_club'=>1,'member_package_branch_store_id'=>1];
        $this->assertSame([$candisari],$filter->invoke($controller,[$foreign,$candisari],3));
        $this->assertSame([$foreign],$filter->invoke($controller,[$foreign,$candisari],2));
        $this->assertSame([],$filter->invoke($controller,[$candisari],2));
        $this->assertSame([$candisari,$allClub],$filter->invoke($controller,[$foreign,$candisari,$allClub],3));
        $this->assertSame([$allClub],$filter->invoke($controller,[$allClub],2));
    }
}
